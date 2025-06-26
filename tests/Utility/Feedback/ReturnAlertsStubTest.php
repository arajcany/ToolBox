<?php

namespace Utility\Feedback;

use arajcany\ToolBox\Utility\Feedback\ReturnAlertsStub;
use PHPUnit\Framework\TestCase;

class ReturnAlertsStubTest extends TestCase
{
    public function testAddSuccessAlert(): void
    {
        $stub = new ReturnAlertsStub();
        $stub->addSuccessAlerts("Test success message");

        $alerts = $stub->getSuccessAlerts();
        $this->assertNotEmpty($alerts);
        $this->assertStringContainsString("Test success message", array_values($alerts)[0]);
    }

    public function testAddDangerAlert(): void
    {
        $stub = new ReturnAlertsStub();
        $stub->addDangerAlerts("Test danger message");

        $alerts = $stub->getDangerAlerts();
        $this->assertNotEmpty($alerts);
        $this->assertStringContainsString("Test danger message", array_values($alerts)[0]);
    }

    public function testSmartAlerts(): void
    {
        $stub = new ReturnAlertsStub();
        $stub->addSmartAlerts([
            'error occurred in module',
            'backup completed successfully',
            'disk warning threshold exceeded',
            'FYI: task has started'
        ]);

        $all = $stub->getAllAlerts();

        $this->assertCount(1, $all['danger']);
        $this->assertCount(1, $all['success']);
        $this->assertCount(1, $all['warning']);
        $this->assertCount(1, $all['info']);
    }

    public function testHighestAlertLevel(): void
    {
        $stub = new ReturnAlertsStub();
        $this->assertEquals('success', $stub->getHighestAlertLevel());

        $stub->addInfoAlerts("Some info");
        $this->assertEquals('info', $stub->getHighestAlertLevel());

        $stub->addWarningAlerts("Be cautious");
        $this->assertEquals('warning', $stub->getHighestAlertLevel());

        $stub->addDangerAlerts("Critical error");
        $this->assertEquals('danger', $stub->getHighestAlertLevel());
    }

    public function testClearAllReturnAlerts(): void
    {
        $stub = new ReturnAlertsStub();
        $this->triggerAlerts($stub);

        $this->assertNotEmpty($stub->getAllAlerts());
        $stub->clearAllReturnAlerts();
        $this->assertEmpty($stub->getAllAlerts()['danger']);
        $this->assertEmpty($stub->getAllAlerts()['warning']);
        $this->assertEmpty($stub->getAllAlerts()['info']);
        $this->assertEmpty($stub->getAllAlerts()['success']);
        $this->assertEquals(0, $stub->getReturnValue());
        $this->assertEquals('', $stub->getReturnMessage());
    }

    public function testGetAllAlertsLogSequence(): void
    {
        $stub = new ReturnAlertsStub();
        $this->triggerAlerts($stub);

        $logs = $stub->getAllAlertsLogSequence();
        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
        $this->assertStringContainsString("SUCCESS:", implode("\n", $logs));
        $this->assertStringContainsString("DANGER:", implode("\n", $logs));
        $this->assertStringContainsString("WARNING:", implode("\n", $logs));
        $this->assertStringContainsString("INFO:", implode("\n", $logs));
    }

    private function triggerAlerts(ReturnAlertsStub $stub): void
    {
        $stub->addSuccessAlerts("Operation completed successfully.");
        $stub->addDangerAlerts("System failure detected.");
        $stub->addWarningAlerts("Disk space is low.");
        $stub->addInfoAlerts("FYI: Backup started.");
    }
}
