# BitePlate — Smart Restaurant Management System (SRMS)

> Unit 27: Advanced Programming · Y/615/1651 · BTEC Level 5 Higher National
> A prototype restaurant management system demonstrating OOP and the GoF design patterns.

BitePlate is a **modular monolith** built with **Laravel 13 / PHP 8.3**. Its defining
characteristic is a **pure-PHP domain core** (`app/Domain`) that contains all ten design
patterns from the brief with **zero framework dependencies** — the framework, database,
identity provider and message bus all sit *around* that core and depend on *it*, never the
reverse (Dependency Inversion / Clean Architecture).

---

## 1. Language & IDE justification

**PHP 8.3 + Laravel 13**, written in **PhpStorm**.

PHP 8.3 is a mature, strongly-featured OOP language: enums, `readonly` classes, constructor
property promotion, first-class `match`, union/intersection types and strict typing let the
domain model express design patterns crisply and safely (e.g. the `Money` value object is a
`final readonly class`, table states are real polymorphic objects, allergens are a backed
enum). Laravel supplies the surrounding plumbing — routing, ORM, DI container, validation,
queues — so the project stays focused on the *patterns* rather than framework scaffolding,
while still being production-shaped (FrankenPHP/Octane, Postgres, Redis, Kafka, Keycloak,
LDAP). PhpStorm was chosen for its first-class Laravel/Blade support, refactoring tools and
static analysis, which keep a layered codebase navigable.

---

## 2. Architecture at a glance

```
app/
├── Domain/           ← PURE PHP. All 10 patterns. No Laravel. Unit-tested in isolation.
│   ├── Menu/         Factory Method + Abstract Factory + Composite + Decorator
│   ├── Pricing/      Strategy (+ time-of-day resolver)
│   ├── Ordering/     Order aggregate = Observer subject; guarded lifecycle
│   ├── Tables/       State (object-per-state)
│   ├── Kitchen/      Command (+ undo/redo invoker) + station router
│   ├── History/      Singleton + Iterator
│   ├── Billing/      Facade over tax / tip / split
│   ├── Staff/        Role/permission RBAC model
│   └── Shared/       Money, enums, EventBus & NotificationChannel ports
├── Application/      ← use-cases (orchestration) + repository contracts (ports)
├── Infrastructure/   ← Eloquent repos, Kafka bus, LDAP directory, observers
├── Livewire/         ← POS UI components
└── Models/           ← thin Eloquent persistence records
```

### Design-pattern map

| Pattern | Category | Where | Feature |
|---|---|---|---|
| **Factory Method** | Creational | `Domain/Menu/Factory/MenuItemFactory` | Build Starter/Main/Dessert/Beverage |
| **Abstract Factory** | Creational | `Domain/Menu/Factory/*MenuFactory` | Per-franchise menus (Scenario C) |
| **Singleton** | Creational | `Domain/History/OrderHistoryLog` | Global audit log |
| **Composite** | Structural | `Domain/Menu/ComboMeal` | Combos & set meals |
| **Decorator** | Structural | `Domain/Menu/Customization/*` | Extras, allergen flags, substitutions |
| **Facade** | Structural | `Domain/Billing/BillingFacade` | One-call billing |
| **Command** | Behavioural | `Domain/Kitchen/Command/*` + `KitchenQueue` | Kitchen actions + undo |
| **Observer** | Behavioural | `Domain/Ordering/Order` + `Observer/*` | Order/allergy notifications |
| **Strategy** | Behavioural | `Domain/Pricing/*Pricing` | Happy Hour / Loyalty / Group / Corporate |
| **State** | Behavioural | `Domain/Tables/State/*` | Table lifecycle |
| **Iterator** | Behavioural | `Domain/History/OrderHistoryIterator` | Traverse history for reports |

The three patterns the brief requires in code (Task 3b) — **Command, Singleton, Strategy** —
work together in one flow: a waiter places an order, it is confirmed (Observer fan-out +
**Singleton** audit log), the kitchen works it via **Command** objects with undo, and the
cashier bills it with a runtime-swappable **Strategy** behind the billing **Facade**.

---

## 3. Quick start — local (no Docker)

The app runs out of the box on **SQLite** with a password-less role switcher. This path is
fully tested and is the fastest way to explore the system.

```bash
composer install --ignore-platform-req=ext-ldap   # local PHP has no ext-ldap; the container does
npm install && npm run build
php artisan migrate:fresh --seed
php artisan serve
```

Open <http://localhost:8000>, then sign in by picking a role (Manager, Head Chef, Waiter,
Cashier). Each role sees a different navigation and set of permitted actions.

**Try the end-to-end demonstration from the CLI** (seats a table, builds a customised order,
fires the kitchen queue, bills with Happy Hour, prints analytics):

```bash
php artisan biteplate:demo
```

### Suggested walkthrough
1. **Floor** → *Seat* a party at a free table (watch the State transition Free → Occupied).
2. *Take order* → add a dish, hit **Customise** (extras + "no gluten" + special request — the
   Decorator chain), then **Send to kitchen**. Watch the 🔔 notification bell (Observer).
   Order something with shellfish/nuts to trigger the **allergy alert**.
3. **Kitchen** (as Head Chef/Manager) → *Prepare* → *Mark ready*; hit **Undo** (Command undo).
4. **Billing** (as Cashier/Manager) → pick the bill, switch the **pricing strategy** live, set a
   tip and split — then *Issue bill* and *Settle & clear table*.
5. **Reports** (as Manager) → analytics computed by traversing the history Singleton's Iterator.
6. **Staff** (as Manager) → LDAP directory + the role/permission matrix.

---

## 4. Full stack — Docker

Brings up FrankenPHP+Octane, Postgres, Redis, **Kafka** (KRaft), **Keycloak** (OIDC) and
**OpenLDAP** (federated into Keycloak and read by the in-app staff directory).

```bash
cp .env.example .env && php artisan key:generate   # if you don't already have an APP_KEY
docker compose up --build
```

| Service | URL |
|---|---|
| BitePlate POS | <http://localhost:8000> |
| Keycloak admin | <http://localhost:8080> (`admin` / `admin`) |
| Postgres `localhost:5432` · Redis `localhost:6379` · Kafka `localhost:9092` | |

**Keycloak SSO accounts** (realm `biteplate`, auto-imported): `manager`/`manager`,
`chef`/`chef`, `waiter`/`waiter`, `cashier`/`cashier`. The realm also federates OpenLDAP, whose
seeded staff (`amorgan`, `mrossi`, `slee`, `dkerr`, password `password`) appear in the in-app
**Staff** directory.

> **Browser SSO note:** the app container reaches Keycloak at `keycloak:8080` while your browser
> uses `localhost:8080`. For redirect-based login from the host, add `127.0.0.1 keycloak` to your
> hosts file — or simply use the role switcher, which stays enabled. Kafka events are published by
> `KafkaEventBus` and consumed by the `kafka-consumer` service (`php artisan biteplate:kafka:consume`).

---

## 5. Testing

```bash
php artisan test
```

- `tests/Unit/DomainPatternsTest.php` — pure-domain tests (extend PHPUnit's `TestCase`, **not**
  Laravel's, to prove the pattern core is framework-independent): Money immutability, Decorator,
  Composite, Strategy swap, time resolver, State guards, Command undo, Singleton+Iterator,
  penny-exact bill splitting, polymorphic RBAC.
- `tests/Feature/PosAccessTest.php` — role-based route access (RBAC) and page rendering.

All tests pass: `18 passed (45 assertions)`.

---

## 6. Key technologies

FrankenPHP + Laravel Octane · PostgreSQL · Redis · Apache Kafka (php-rdkafka) · Keycloak (OIDC
via Socialite) · OpenLDAP (LdapRecord) · Livewire + Tailwind CSS 4.

See `EVALUATION.md` for a critical evaluation of the pattern choices, the Singleton trade-offs,
and how the design would change at 50-restaurant scale.
