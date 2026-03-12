<?php

// Template: tests/Pest.php
// Replace: VendorName\PackageName

use VendorName\PackageName\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
