# Dragiyski Commission Task

A commission computation task with.

## Installation

### Docker

To install the application execute:

```
docker built -t commission-task .
```

This will try to install all necessary dependencies.

### Manual

The application require php with bcmath extension and composer installed. Run:

```
composer install
```

to install all dependencies.

## Usage

To use the commission task for computation of commission fee on a CSV file, execute:

```
composer run commission-task my-csv-file.csv
```

or

```
php src/main.php my-csv-file.csv
```

### With Docker

If installed as docker image, the commission task can be run by:

```
docker run -i --rm commission-task - < my-csv-file.csv
```

## Input

The CSV file should match the provided CSV input. Namely it should contain columns in this order:

* `date`: must be in `Y-m-d` format;
* `user_id`: must be integer;
* `user_type`: must be `private` or `business` (lowercase);
* `operation_type`: must be `withdraw` or `deposit` (lowercase);
* `amount`: must be numeric string;
* `currency`: must be 3-letter currency code. The current example supports only `EUR`, `USD`, `JPY`;

## Rules

### Deposit rule

The rule apply 0.03% of the record base value (principal).

### Withdraw rule

Charged based on the user type

#### Business clients

The rule apply 0.5% of the record base value.

#### Private clients

The rules apply 0.3% of the record base value (withdrawn amount).

For the first 3 transactions, there is total combined discount up to `1000.00 EUR`.

## Structure

To support extensibility, the application work as set of services with dependency injection between them. The fee rules are then matched against each record. Some fee rules can nest other fee rules. For example, the rule that match deposit/withdraw executes different rule dependending on the record value.

After it verify a private client has less than 3 transaction in the specified week, the discount is applied on the base value (principal), making traditional fee (that multiplies the rate to the principal) equivalent to any other fee, but this time the principal computation is what is different. This makes it easier to define a complex rule with simple set of predefined rules by just one dependency difference.

Therefore the current functionality can be extended by modifying the dependency-injection (DI) services/config. The config is currently written in PHP, but many frameworks (Symfony, Laravel) abstract the DI configuration to data files (yaml, XML, json, etc). In such case no modification of PHP code is required to change the rules, and no modification of the current code is required to add new functionality.

The class `Model\AmountAt` can also be used with potential future currency converter that support historical data. This can be done with no modification of the current code and simple `$amount instanceof AmountAt`. The current currency converter uses hardcoded currency exchange rates, as this is required for deterministic testing. Additionally most FX APIs with access to historical data are paid and they have limitation on number of API calls. As such this task is does not implement usage of 3rd-party API to get actual exchange rates, but it can be modified to do so, if necessary.

