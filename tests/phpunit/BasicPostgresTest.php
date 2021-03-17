<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

class BasicPostgresTest extends \PHPUnit\Framework\TestCase
{
    use CredentialsPostgres;
    use BasicSetup;
    use BasicTest;
}
