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

### API

This script uses third party APIs from [https://exchangeratesapi.io/](https://exchangeratesapi.io/) in production. Modify `config/prod.services.php` replacing `REPLACE_WITH_ACTUAL_API_KEY` string with your API key for that service. This production uses `convert` service endpoint (not available for the free API keys).

To pass an API key by environment variable instead use replace the aforementioned text with `getenv(YOUR_VARIABLE)` or even better: use dotenv files.

## Usage

To use the commission task for computation of commission fee on a CSV file, execute:

```
composer run commission-task my-csv-file.csv
```

or

```
php src/main.php my-csv-file.csv
```

Running in production should specify the environment in `APP_ENV` variable.

```
APP_ENV=prod php src/main.php my-csv-file.csv
```

### With Docker

If installed as docker image, the commission task can be run by:

```
docker run -i --rm commission-task - < my-csv-file.csv
```

or with specific environment:

```
docker run --env APP_ENV=prod -i --rm commission-task - < my-csv-file.csv
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

The fee for deposit transactions is 0.03%.

The fee for business client withdraw is 0.5%.

The fee for private client withdraw is 0.3%, however, private clients receive up to EUR 1000.00 (equivalent to the transaction currency) discount for the first three transactions each week (Mon-Sun). If transaction exceeds EUR 1000.00, 0.3% is applied to the exceeding amount only.

## Structure

To support extensibility, the application work as set of services with dependency injection between them. The fee rules are then matched against each record. Some fee rules can nest other fee rules. For example, the rule that match deposit/withdraw executes different rule dependending on the record value.

After it verify a private client has less than 3 transaction in the specified week, the discount is applied on the base value (principal), making traditional fee (that multiplies the rate to the principal) equivalent to any other fee, but this time the principal computation is what is different. This makes it easier to define a complex rule with simple set of predefined rules by just one dependency difference.

Therefore the current functionality can be extended by modifying the dependency-injection (DI) services/config. The config is currently written in PHP, but many frameworks (Symfony, Laravel) abstract the DI configuration to data files (yaml, XML, json, etc). In such case no modification of PHP code is required to change the rules, and no modification of the current code is required to add new functionality.

The class `Model\AmountAt` can also be used with potential future currency converter that support historical data. This can be done with no modification of the current code and simple `$amount instanceof AmountAt`. The current currency converter uses hardcoded currency exchange rates, as this is required for deterministic testing. Additionally most FX APIs with access to historical data are paid and they have limitation on number of API calls. As such this task is does not implement usage of 3rd-party API to get actual exchange rates, but it can be modified to do so, if necessary.

## Caveats

It is a requirement for the script to do all compilations in memory. Cache files, databases and other persistant storage is not supported. As such the script will not store any request and it can do 1-3 API requests per transaction. Make sure not to process large CSV files that can exhaust the number of available API calls to from [https://exchangeratesapi.io/](https://exchangeratesapi.io/) for your access key.

# Nockups

The project now contains simulation/mockup of the third-party service [https://exchangeratesapi.io/](https://exchangeratesapi.io/). The conversion rates in this service are random values between `0.1` and `2.0`, but deterministic (i.e. same value will be returned for the same currency set and the same date). Do note that the mockup does not guarantee proper currency inversion: for example `EURUSD` divided by `USDEUR` will not be unlikely to result in `1.00`.

To run the mockup, enter `mockup/exchangeratesapi.io` and run `docker-composer up`.

**Note:** Despite specification in `depends_on`, in certain versions of docker compose it is possible that `nginx` runs before `wsgi`, resulting in exiting with error code 137. This could happen when run for the first time after rebuild. Newer versions of docker compose allow more precise specification, telling that `wsgi` must not only be in "started", but "healthy" state before `nginx` run, and restart the `nginx` automatically if that is not the case. If you see the above error, please restart the compose manually or update compose to the latest version.
