<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Student REST API

The JSON API is available at `/api/students`. Requests require HTTP Basic
authentication using a registered SIMS account's email and password. Use HTTPS
when sending credentials outside a local development environment.

| Method | Endpoint | Access |
|---|---|---|
| `GET` | `/api/students` | Authenticated staff or administrator; paginated |
| `GET` | `/api/students/{id}` | Authenticated staff or administrator |
| `POST` | `/api/students` | Authenticated staff or administrator |
| `PUT` | `/api/students/{id}` | Authenticated staff or administrator |
| `DELETE` | `/api/students/{id}` | Administrator only |

Create and update requests accept JSON fields `student_number`, `first_name`,
`last_name`, `email`, `course`, and `year_level`. The optional `semester` field
accepts `First`, `Second`, or `Summer`; `date_of_birth` is also optional.
Student number and email must be unique. Validation failures return `422`
JSON responses. A student with enrollments cannot be deleted (`409`).

Example request:

```http
POST /api/students
Accept: application/json
Content-Type: application/json
Authorization: Basic <base64(email:password)>

{
  "student_number": "STU-1001",
  "first_name": "Alex",
  "last_name": "Santos",
  "email": "alex@example.com",
  "course": "Computer Science",
  "year_level": "1st Year",
  "semester": "First"
}
```

## Student portal accounts

Student accounts are created by an administrator from a student's detail page.
The account uses the email on the student record and an administrator-provided
password. After signing in, students are sent to `/my-enrollments`, which only
shows subjects linked to their own student record. Public self-registration is
disabled; staff and administrator access is assigned by the system
administrator.

## Multi-subject enrollment

On the **Enroll Student** form, staff and administrators can select multiple
subjects for the same student, school year, semester, status, and enrollment
date. Search, select-visible, and clear controls help manage the selection.
The batch is validated as a whole; if a selected subject is already enrolled
for that student and term, no new enrollments in the batch are created.

## Program curriculum imports

Administrators can open **Curricula** from the sidebar to import a program
curriculum as CSV or XLSX. Use the downloadable CSV template or upload a
curriculum workbook with a course table. The first worksheet of a program
workbook may contain a title and revision metadata, a semester course plan,
and an optional elective-options table. Supported columns include:

`subject_code`, `subject_name`, `units`, `year_level`, `semester`

The workbook importer also recognizes `Course Code`, `Descriptive Title`,
`Total Units`, `Year`, `Semester`, lecture/lab units and hours, prerequisites,
co-requisites, course groups, and elective types. Each row represents a course
offering; elective options are grouped separately. Zero-unit courses are
supported. Program and revision details are read from supported workbook
metadata, or can be entered manually. Imports are limited to 2,000 courses and
10 MB. Existing subject codes are linked without changing the subject catalog;
the curriculum preserves its own code, title, and units. PHP's `gd` and `zip`
extensions must be enabled for XLSX support. Curriculum details use a
responsive landscape layout and the print action sets the page orientation to
landscape for PDF export.

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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
