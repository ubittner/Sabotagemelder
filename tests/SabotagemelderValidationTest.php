<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class SabotagemelderValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary_Sabotagemelder(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateModule_Sabotagemelder(): void
    {
        $this->validateModule(__DIR__ . '/../Sabotagemelder');
    }
}