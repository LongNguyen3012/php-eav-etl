# PHP EAV ETL

A lightweight ETL library for converting WordPress EAV (Entity-Attribute-Value) 
data into relational tables. Built to solve the N+1 query problem in analytics.

## The Problem

WordPress stores custom data in `wp_postmeta` using an EAV pattern — each 
field is a separate row. Complex analytics queries require multiple JOINs, 
scanning thousands of rows for a single answer.

## The Solution

A three-stage pipeline: wp_postmeta (EAV) → Extract → Transform → Load → Relational Table


## Benchmarks

Comparing EAV queries vs. relational queries on 1,000 records:

| Query | EAV | Relational | Speedup |
|-------|-----|------------|---------|
| Count by category | 17 ms | 1.3 ms | **12.7x** |
| Filter by 2 attributes | 16 ms | 1.5 ms | **10.6x** |
| Top 5 by metric | 7 ms | 1.1 ms | **6.8x** |
| Group by type | 14 ms | 2.5 ms | **5.7x** |

**Result:** ~9x faster average, with 26x fewer rows scanned.

## Design Principles

- **Idempotent** — safe to re-run without duplicates
- **Prepared statements** — real prepared statements (`EMULATE_PREPARES = false`)
- **Type casting** — EAV stores everything as string; convert to proper types
- **Whitelist mapping** — only extract known fields, ignore plugin metadata
- **Separated concerns** — Extract, Transform, Load are independent classes

## Requirements

- PHP 8.0+
- MySQL 5.7+ (tested on 5.7.44)

## Installation

```bash
git clone https://github.com/LongNguyen3012/php-eav-etl.git
cd php-eav-etl
composer install
