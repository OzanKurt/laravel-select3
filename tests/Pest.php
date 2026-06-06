<?php

// The builder and search core are framework-light, so the suite runs on plain
// PHPUnit test cases without booting a full Laravel app.
uses(PHPUnit\Framework\TestCase::class)->in(__DIR__);
