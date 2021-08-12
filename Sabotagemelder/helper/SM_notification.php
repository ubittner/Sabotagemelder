<?php

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Sabotagemelder/tree/main/Sabotagemelder
 */

/** @noinspection PhpUnused */

declare(strict_types=1);

trait SM_notification
{
    public function SendDailyNotification(): void
    {
        $this->SetDailyNotificationTimer();
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->GetValue('SabotageDetection')) {
            return;
        }
        $this->UpdateState();
        if (!$this->ReadPropertyBoolean('UseDailyNotification')) {
            return;
        }
        $title = 'Sabotagemelder';
        $location = $this->ReadPropertyString('LocationDesignation');
        $timestamp = (string) date('d.m.Y, H:i:s');
        $unicode = json_decode('"\u2705"'); # white_check_mark
        $actualState = $this->GetValue('State');
        $statusDescription = 'OK';
        if ($actualState) {
            $unicode = json_decode('"\u2757"'); #  red exclamation mark
            $statusDescription = 'Sabotage erkannt';
        }
        // WebFront Notification
        $text = $location . "\n" . $unicode . ' ' . $statusDescription . "\n" . $timestamp;
        $this->SendWebFrontNotification($title, $text, '');
        // WebFront Push Notification
        $text = "\n" . $location . "\n" . $unicode . ' ' . $statusDescription . "\n" . $timestamp;
        $this->SendWebFrontPushNotification($title, $text, 'alarm');
        // Mail
        $subject = 'Sabotagemelder ' . $location . ' - ' . $unicode . ' ' . $statusDescription;
        $text = "Status:\n\n" . $timestamp . ', Sabotagemelder ' . $location . ' - ' . $unicode . ' ' . $statusDescription . "\n\n";
        $sensorStateList = "Sabotagesensoren: \n\n";
        $sensors = json_decode($this->GetBuffer('SensorStateList'));
        if (!empty($sensors)) {
            foreach ($sensors as $sensor) {
                $sensorStateList .= $sensor->unicode . ' ' . $sensor->name . "\n";
            }
        }
        $text .= $sensorStateList;
        $this->SendMailNotification($subject, $text);
        // NeXXt Mobile SMS
        $text = $title . "\n" . $location . "\n" . $statusDescription . "\n" . $timestamp;
        $this->SendNeXXtMobileSMS($text);
        // Sipgate SMS
        $text = $title . "\n" . $location . "\n" . $unicode . ' ' . $statusDescription . "\n" . $timestamp;
        $this->SendSipgateSMS($text);
        // Telegram Message
        $this->SendTelegramMessage($text);
    }

    #################### Protected

    protected function SendWebFrontNotification(string $Title, string $Text, string $Icon): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->GetValue('SabotageDetection')) {
            return;
        }
        $id = $this->ReadPropertyInteger('WebFrontNotification');
        if ($id == 0 || @!IPS_ObjectExists($id)) {
            return;
        }
        //@WFC_SendNotification($id, $Title, $Text, $Icon, $this->ReadPropertyInteger('DisplayDuration'));
        $scriptText = 'WFC_SendNotification(' . $id . ', "' . $Title . '", "' . $Text . '", "' . $Icon . '", ' . $this->ReadPropertyInteger('DisplayDuration') . ');';
        IPS_RunScriptText($scriptText);
    }

    protected function SendWebFrontPushNotification(string $Title, string $Text, string $Sound, int $TargetID = 0): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->GetValue('SabotageDetection')) {
            return;
        }
        $id = $this->ReadPropertyInteger('WebFrontPushNotification');
        if ($id == 0 || @!IPS_ObjectExists($id)) {
            return;
        }
        //@WFC_PushNotification($id, $Title, $Text, $Sound, $TargetID);
        $scriptText = 'WFC_PushNotification(' . $id . ', "' . $Title . '", "' . $Text . '", "' . $Sound . '", ' . $TargetID . ');';
        IPS_RunScriptText($scriptText);
    }

    protected function SendMailNotification(string $Subject, string $Text): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->GetValue('SabotageDetection')) {
            return;
        }
        $id = $this->ReadPropertyInteger('Mailer');
        if ($id == 0 || @!IPS_ObjectExists($id)) {
            return;
        }
        //@MA_SendMessage($id, $Subject, $Text);
        $scriptText = 'MA_SendMessage(' . $id . ', "' . $Subject . '", "' . $Text . '");';
        IPS_RunScriptText($scriptText);
    }

    protected function SendNeXXtMobileSMS(string $Text): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->GetValue('SabotageDetection')) {
            return;
        }
        $id = $this->ReadPropertyInteger('NeXXtMobile');
        if ($id == 0 || @!IPS_ObjectExists($id)) {
            return;
        }
        //@SMSNM_SendMessage($id, $Text);
        $scriptText = 'SMSNM_SendMessage(' . $id . ', "' . $Text . '");';
        IPS_RunScriptText($scriptText);
    }

    protected function SendSipgateSMS(string $Text): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->GetValue('SabotageDetection')) {
            return;
        }
        $id = $this->ReadPropertyInteger('Sipgate');
        if ($id == 0 || @!IPS_ObjectExists($id)) {
            return;
        }
        //@SMSSG_SendMessage($id, $Text);
        $scriptText = 'SMSSG_SendMessage(' . $id . ', "' . $Text . '");';
        IPS_RunScriptText($scriptText);
    }

    protected function SendTelegramMessage(string $Text): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->GetValue('SabotageDetection')) {
            return;
        }
        $id = $this->ReadPropertyInteger('Telegram');
        if ($id == 0 || @!IPS_ObjectExists($id)) {
            return;
        }
        //@TB_SendMessage($id, $Text);
        $scriptText = 'TB_SendMessage(' . $id . ', "' . $Text . '");';
        IPS_RunScriptText($scriptText);
    }

    #################### Private

    private function SetDailyNotificationTimer(): void
    {
        if (!$this->ReadPropertyBoolean('UseDailyNotification')) {
            $milliseconds = 0;
        } else {
            $now = time();
            $time = json_decode($this->ReadPropertyString('DailyNotificationTime'));
            $hour = $time->hour;
            $minute = $time->minute;
            $second = $time->second;
            $definedTime = $hour . ':' . $minute . ':' . $second;
            if (time() >= strtotime($definedTime)) {
                $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
            } else {
                $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
            }
            $milliseconds = ($timestamp - $now) * 1000;
        }
        $this->SetTimerInterval('DailyNotification', $milliseconds);
    }
}