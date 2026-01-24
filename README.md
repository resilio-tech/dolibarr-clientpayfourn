# ClientPayFourn - Dolibarr Module

Module for linking customer and supplier payments. Allows linking a customer invoice to a supplier invoice to manage compensations and cross-payments.

## Features

- Link customer invoice ↔ supplier invoice
- Payment compensation management
- Document link tracking

---

## Installation

### Prerequisites

- Dolibarr >= 11.0
- PHP >= 7.0

### Module Installation

1. Copy the `clientpayfourn` folder into `htdocs/custom/`
2. Enable the module in **Setup > Modules > Other**

---

## Usage

### Use Cases

This module is useful when:
- A customer is also a supplier
- You want to compensate a customer invoice with a supplier invoice
- You want to track links between sales and purchase documents

### Workflow

1. Create a link between a customer invoice and a supplier invoice
2. The link is saved with a status
3. Amounts can be compensated

---

## Architecture

### File Structure

```
clientpayfourn/
├── class/
│   └── linkclientpayfourn.class.php    # Link object
├── core/modules/
│   └── modClientPayFourn.class.php     # Module descriptor
├── admin/
│   ├── setup.php                       # Configuration
│   └── about.php                       # About
├── lib/
│   └── clientpayfourn.lib.php          # Common functions
├── js/
│   └── clientpayfourn.js.php           # JavaScript
├── sql/                                # Database tables
└── langs/                              # Translations
```

### Main Class

#### `LinkClientPayFourn`
Represents a customer invoice ↔ supplier invoice link:

| Field | Type | Description |
|-------|------|-------------|
| `rowid` | int | Technical ID |
| `fk_facture_client` | int | Customer invoice ID |
| `fk_facture_fourn` | int | Supplier invoice ID |
| `status` | int | Status (0=draft, 1=validated, 9=canceled) |

### SQL Table

```
llx_clientpayfourn_linkclientpayfourn  # Invoice links
```

---

## Development

### Dolibarr Tables Used

| Table | Usage |
|-------|-------|
| `llx_facture` | Customer invoices |
| `llx_facture_fourn` | Supplier invoices |

---

## License

GPLv3 - See COPYING file
