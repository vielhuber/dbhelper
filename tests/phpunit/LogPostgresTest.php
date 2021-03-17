<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

class LogPostgresTest extends \PHPUnit\Framework\TestCase
{
    use CredentialsPostgres;
    use LogSetup;
    use LogTest;
}
