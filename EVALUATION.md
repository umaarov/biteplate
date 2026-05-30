# Technical Evaluation

## Were Command, Singleton and Strategy the best fit?

**Strategy (pricing) — yes, unequivocally.** Pricing rules change for business reasons, often at
runtime (Happy Hour, loyalty, group, corporate, weekend surcharge), and the billing code must not
care which is active. Encapsulating each rule behind `PricingStrategy::calculate()` makes adding a
rule a new class and nothing else. The alternative — a `switch` on a pricing "type" inside the
billing logic — was rejected because every new rule would edit and risk regressing tried-and-tested
billing code (open/closed violation). A close cousin, the **Specification** pattern, was considered
for composing rules (e.g. loyalty *and* group); for the current requirements a single strategy plus
the `TimeOfDayPricingResolver` is simpler and sufficient.

**Command (kitchen queue) — yes.** The brief explicitly needs undo, reprioritise and a history of
actions; reifying each action as an object with `execute()`/`undo()` is the textbook fit and gave us
undo/redo almost for free. The alternative of calling methods on the order directly cannot capture
"what just happened" for reversal. A **Memento** would store whole-state snapshots — heavier than the
targeted state captured inside each command.

**Singleton (order history) — fit, with reservations.** A single global audit log matches the
brief, but Singleton is the most criticised GoF pattern, and rightly so.

## Singleton trade-offs: testability and thread safety

- **Testability.** A process-wide static is hidden global state: tests can leak into one another.
  I mitigated this with an explicit `OrderHistoryLog::reset()` that test setup and the per-request
  Octane hook both call, so each test/request starts clean. Even so, the cleaner design is to depend
  on an *interface* and let the DI container manage lifetime — which is exactly what the rest of the
  app does (`OrderHistoryRepository`), reserving the classic Singleton for the brief's requirement.
- **Thread / worker safety.** Under FrankenPHP/Octane the worker is **long-lived**, so a naive static
  would bleed data across requests. The `DomainServiceProvider` resets the singleton on every
  `RequestReceived` event. The durable source of truth is the append-only `order_history` table; the
  in-memory Singleton is rehydrated from it per request for analytics. PHP's shared-nothing model
  means there is no in-process multithreading to guard, but the *shared mutable state* problem is real
  and is the reason the persistent store, not the static, is authoritative.

## Scaling to a 50-restaurant chain on one central database

Several decisions would change:

1. **The Singleton must go (or be scoped).** One global in-memory log across 50 branches is wrong; the
   history must be branch-partitioned in the database and queried with a `branch_id` filter. The
   Iterator and reporting code are unaffected — they already read through an abstraction — which is the
   payoff of not coupling reports to storage.
2. **Order IDs** (currently `ORD-` + a count) would collide across branches; switch to ULIDs/UUIDs or a
   per-branch prefix.
3. **The Abstract Factory** already isolates per-branch menus, so menu divergence scales cleanly;
   it would be driven by a `branch` column rather than config.
4. **Kafka becomes load-bearing**, not optional: per-branch topics/partitions, and the event bus is the
   backbone for cross-branch analytics and the central dashboard. The existing `EventBus` port means
   the application code does not change.
5. **Read/write split & caching:** central reporting would move to read replicas or a warehouse; the
   repository interfaces localise that change to the infrastructure layer.

The recurring theme is that the **ports-and-adapters** structure absorbs scale changes in the
infrastructure layer while the pattern-rich domain stays put — which is the whole argument for keeping
the patterns framework-agnostic.
