<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Updating the live server: RF Account and Saving Account

The existing bank ledger is retained as RF Account. Existing `bank_transactions.id`
and `source_id` values remain unchanged. Existing payment sources stored as `bank`
continue to use that same value and display as RF Account. This project does not have
a separate numeric bank-account ID or a `bank_accounts` table; the new
`bank_transactions.bank_account` column defaults existing rows to `rf_account`.
Saving Account uses the new `saving_account` value.

After backing up the live database and deploying the code, run these commands from
the application directory using the live server's existing `.env`:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Use the normal incremental migration command above. Do not use `migrate:fresh`,
`migrate:refresh`, or setup/demo seeders for this upgrade; they are not part of the
live update. `SingleBankAccountSetupSeeder` reconciles fixed opening balances and
must not be rerun as part of deployment. No account-creation or balance-reset seeder
is required. The incremental migrations add fields to the existing tables.

After upgrading, confirm that pre-existing transaction IDs are unchanged, existing
bank transactions show RF Account, and Saving Account starts at zero until a
transaction is explicitly assigned to it. Past commission payments with no recorded
payment source retain that unknown source until the closing is edited.

## Synchronized staff commission payments

Run `php artisan migrate --force` after deploying the synchronization update.
The migration adds `staff_transactions.monthly_commission_id` and backfills one
linked transaction for each existing positive closing payment. Existing staff
transaction and bank ledger IDs are preserved; no seeders are needed.

Advances and payouts entered in Staff Transactions refresh their assigned commission
month. A closing payment entered through Monthly Closing or Staff Commission
Balances appears in Staff Transactions as **Monthly closing payment**. Editing its
amount, source, or payment date updates the same closing payment and recalculates
later balances. Deleting the linked transaction clears that closing payment.

Linked closing payments stay assigned to their original staff member and commission
month. Their bank ledger remains attached to the existing monthly commission ID;
they are excluded from advance/payout deductions so cash, bank balances, commission
payments, and payment history count the payment once. Historical payments with no
recorded account show **Not recorded**; historical payments without a payment date
use the end of their commission month for the linked transaction.

## Salary payments for other staff

Choose **Salary payment** in Staff Transactions and select the staff member,
payment date, salary month, amount, and collection cash/RF Account/Saving Account.
All active staff are selectable; salary payments are recorded individually.

Salary payments are owner-paid withdrawals, excluded from business expenses and
commission calculations. Cash salaries reduce cash to deposit once; bank salaries
debit the selected RF or Saving Account once. Edit or delete them in Staff Transactions.

Deploy the code and run `php artisan migrate --force`. The correction migration removes
previously generated salary expenses and refreshes affected commission balances,
while retaining existing staff payment and bank transaction IDs.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
