<?php

/**
 * @project       Batteriemelder/Batteriemelder
 * @file          BATM_Config.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait BATM_Config
{
    /**
     * Reloads the configuration form.
     *
     * @return void
     */
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    /**
     * Expands or collapses the expansion panels.
     *
     * @param bool $State
     * false =  collapse,
     * true =   expand
     *
     * @return void
     */
    public function ExpandExpansionPanels(bool $State): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->UpdateFormField('Panel' . $i, 'expanded', $State);
        }
    }

    /**
     * Modifies a configuration button.
     *
     * @param string $Field
     * @param string $Caption
     * @param int $ObjectID
     * @return void
     */
    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) {
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    /**
     * Modifies a trigger list configuration button
     *
     * @param string $Field
     * @param string $Condition
     * @return void
     */
    public function ModifyTriggerListButton(string $Field, string $Condition): void
    {
        $id = 0;
        $state = false;
        //Get variable id
        $primaryCondition = json_decode($Condition, true);
        if (array_key_exists(0, $primaryCondition)) {
            if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    $state = true;
                }
            }
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $id . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $id);
    }

    /**
     * Gets the configuration form.
     *
     * @return false|string
     * @throws Exception
     */
    public function GetConfigurationForm()
    {
        $form = [];

        ########## Elements

        //Configuration buttons
        $form['elements'][0] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration ausklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, true);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration einklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, false);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration neu laden',
                        'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                    ]
                ]
            ];

        //Info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $module = IPS_GetModule(self::MODULE_GUID);
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel1',
            'caption' => 'Info',
            'items'   => [
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleID',
                    'caption' => "ID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Modul:\t\t" . $module['ModuleName']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Präfix:\t\t" . $module['Prefix']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Version:\t\t" . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Entwickler:\t" . $library['Author']
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        ##### Trigger list

        $triggerListValues = [];
        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($variables as $variable) {
            $actualStatus = 'Existiert nicht!';
            $lastUpdate = 'Nie';
            $rowColor = '#FFC0C0'; //red
            //Primary condition
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) {
                            $rowColor = '#C0FFC0'; //light green
                            $actualStatus = 'OK';
                            //Check battery
                            if ($variable['CheckBattery'] && IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                                $rowColor = '#FFFFC0'; //yellow
                                $actualStatus = 'Batterie schwach!';
                            }
                            //Check update
                            $variableUpdate = IPS_GetVariable($id)['VariableUpdated'];
                            if ($variableUpdate != 0) {
                                $lastUpdate = date('d.m.Y', $variableUpdate);
                            }
                            if ($variable['CheckUpdate']) {
                                if ($variableUpdate == 0) {
                                    $rowColor = '#FFC0C0'; //red
                                    $actualStatus = 'Überfällige Aktualisierung!';
                                }
                                $now = time();
                                $dateDifference = ($now - $variableUpdate) / (60 * 60 * 24);
                                $updatePeriod = $variable['UpdatePeriod'];
                                if ($dateDifference > $updatePeriod) {
                                    $rowColor = '#FFC0C0'; //red
                                    $actualStatus = 'Überfällige Aktualisierung!';
                                }
                            }
                            //Monitoring disabled
                            if (!$variable['CheckBattery'] && !$variable['CheckUpdate']) {
                                $actualStatus = 'Prüfungen deaktiviert!';
                                $rowColor = '#C0C0FF'; //purple
                            }
                            if (!$variable['Use']) {
                                $rowColor = '#DFDFDF'; //grey
                                $actualStatus = 'Deaktiviert!';
                            }
                        }
                    }
                }
            }
            $triggerListValues[] = ['ActualStatus' => $actualStatus, 'LastUpdate' => $lastUpdate, 'rowColor' => $rowColor];
        }

        ##### Element: Trigger list

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel2',
            'caption' => 'Auslöser',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'TriggerList',
                    'caption'  => 'Auslöser',
                    'rowCount' => 20,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ActualStatus',
                            'caption' => 'Aktueller Status',
                            'width'   => '200px',
                            'add'     => ''
                        ],
                        [
                            'caption' => 'Bezeichnung',
                            'name'    => 'Designation',
                            'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $TriggerList["PrimaryCondition"]);',
                            'width'   => '300px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Bemerkung',
                            'name'    => 'Comment',
                            'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $TriggerList["PrimaryCondition"]);',
                            'width'   => '200px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'BatteryType',
                            'width'   => '200px',
                            'add'     => '',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Benutzerdefiniert',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => '1 x LR44',
                                        'value'   => '1 x LR44'
                                    ],
                                    [
                                        'caption' => '2 x LR44',
                                        'value'   => '2 x LR44'
                                    ],
                                    [
                                        'caption' => '1 x AAA',
                                        'value'   => '1 x AAA'
                                    ],
                                    [
                                        'caption' => '2 x AAA',
                                        'value'   => '2 x AAA'
                                    ],
                                    [
                                        'caption' => '3 x AAA',
                                        'value'   => '3 x AAA'
                                    ],
                                    [
                                        'caption' => '1 x AA',
                                        'value'   => '1 x AA'
                                    ],
                                    [
                                        'caption' => '2 x AA',
                                        'value'   => '2 x AA'
                                    ],
                                    [
                                        'caption' => '3 x AA',
                                        'value'   => '3 x AA'
                                    ],
                                    [
                                        'caption' => '1 x C',
                                        'value'   => '1 x C'
                                    ],
                                    [
                                        'caption' => '2 x C',
                                        'value'   => '2 x C'
                                    ],
                                    [
                                        'caption' => '3 x C',
                                        'value'   => '3 x C'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Benutzerdefinierter Batterietyp',
                            'name'    => 'UserDefinedBatteryType',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'SpacerCheckBattery',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterieprüfung:',
                            'name'    => 'LabelCheckBattery',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'italic' => true,
                                'bold'   => true
                            ]
                        ],
                        [
                            'caption' => 'Batterieprüfung',
                            'name'    => 'CheckBattery',
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Mehrfachauslösung',
                            'name'    => 'UseMultipleAlerts',
                            'width'   => '200px',
                            'add'     => false,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Bedingung:',
                            'name'    => 'LabelCheckBatteryCondition',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'PrimaryCondition',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectCondition'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'SpacerCheckUpdate',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierungsprüfung:',
                            'name'    => 'LabelCheckUpdate',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'italic' => true,
                                'bold'   => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierungsprüfung',
                            'name'    => 'CheckUpdate',
                            'width'   => '200px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Zeitraum',
                            'name'    => 'UpdatePeriod',
                            'width'   => '100px',
                            'add'     => 3,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Tage'
                            ]
                        ],
                        [
                            'name'    => 'LastUpdate',
                            'caption' => 'Letzte Aktualisierung',
                            'width'   => '190px',
                            'add'     => 0
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'SpacerBatteryReplacement',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batteriewechsel:',
                            'name'    => 'LabelBatteryReplacement',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'italic' => true,
                                'bold'   => true
                            ]
                        ],
                        [
                            'caption' => 'Letzter Batteriewechsel',
                            'name'    => 'LastBatteryReplacement',
                            'width'   => '200px',
                            'add'     => '{"year":0,"month":0,"day":0}',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectDate'
                            ]
                        ]
                    ],
                    'values' => $triggerListValues,
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'TriggerListConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        ##### Element: Battery list

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel3',
            'caption' => 'Batterieliste',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Anzeigeoptionen',
                    'bold'    => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableUpdateOverdue',
                    'caption' => 'Aktualisierung überfällig'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'UpdateOverdueStatusText',
                    'caption' => 'Bezeichnung'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLowBattery',
                    'caption' => 'Batterie schwach'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'LowBatteryStatusText',
                    'caption' => 'Bezeichnung'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableBatteryOK',
                    'caption' => 'Batterie OK'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'BatteryOKStatusText',
                    'caption' => 'Bezeichnung'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableCheckDisabled',
                    'caption' => 'Überwachung deaktiviert'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'MonitoringDisabledStatusText',
                    'caption' => 'Bezeichnung'
                ]
            ]
        ];

        ##### Immediate notification

        //Immediate notification
        $immediateNotificationValues = [];
        foreach (json_decode($this->ReadPropertyString('ImmediateNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $immediateNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Immediate push notification
        $immediatePushNotificationValues = [];
        foreach (json_decode($this->ReadPropertyString('ImmediatePushNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $immediatePushNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Immediate mailer notification
        $immediateNotificationMailerValues = [];
        foreach (json_decode($this->ReadPropertyString('ImmediateMailerNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $immediateNotificationMailerValues[] = ['rowColor' => $rowColor];
        }

        ##### Element: Immediate Notification

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel4',
            'caption' => 'Sofortige Benachrichtigung',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [

                        [
                            'type'    => 'Label',
                            'caption' => 'Benachrichtigungen zurücksetzen um '
                        ],
                        [
                            'type'    => 'SelectTime',
                            'name'    => 'ImmediateNotificationResetTime',
                            'caption' => 'Uhrzeit'
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Immediate notification

                [
                    'type'    => 'Label',
                    'caption' => 'Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediateNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "ImmediateNotificationConfigurationButton", "ID " . $ImmediateNotification["ID"] . " konfigurieren", $ImmediateNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'UpdateOverdueIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'UpdateOverdueTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s Aktualisierung überfällig',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'UpdateOverdueDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'LowBatteryIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'LowBatteryTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️   %1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'LowBatteryDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'BatteryOKIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'BatteryOKTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s Batterie OK',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'BatteryOKDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ]
                    ],
                    'values' => $immediateNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'ImmediateNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Immediate push notification

                [
                    'type'    => 'Label',
                    'caption' => 'Push-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediatePushNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "ImmediatePushNotificationConfigurationButton", "ID " . $ImmediatePushNotification["ID"] . " konfigurieren", $ImmediatePushNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'UpdateOverdueTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s Aktualisierung überfällig',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'UpdateOverdueSound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'UpdateOverdueTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'LowBatteryTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️  %1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'LowBatterySound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'LowBatteryTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'BatteryOKTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s Batterie OK',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'BatteryOKSound',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'BatteryOKTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ]
                    ],
                    'values' => $immediatePushNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'ImmediatePushNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Immediate email notification

                [
                    'type'    => 'Label',
                    'caption' => 'E-Mail',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediateMailerNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Mailer',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "ImmediateNotificationMailerConfigurationButton", "ID " . $ImmediateNotificationMailer["ID"] . " konfigurieren", $ImmediateNotificationMailer["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::MAILER_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Betreff',
                            'name'    => 'Subject',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder (Standort)',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseUpdateOverdueVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseUpdateOverdueBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseLowBatteryVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseLowBatteryBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseBatteryOKVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseBatteryOKBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
                    ],
                    'values' => $immediateNotificationMailerValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "Mailer");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'ImmediateNotificationMailerConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ]
            ]
        ];

        ##### Daily notification

        //Daily notification
        $dailyNotificationValues = [];
        foreach (json_decode($this->ReadPropertyString('DailyNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $dailyNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Daily push notification
        $dailyPushNotificationValues = [];
        foreach (json_decode($this->ReadPropertyString('DailyPushNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $dailyPushNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Daily mailer notification
        $dailyNotificationMailerValues = [];
        foreach (json_decode($this->ReadPropertyString('DailyMailerNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $dailyNotificationMailerValues[] = ['rowColor' => $rowColor];
        }

        ##### Element: Daily notification

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel5',
            'caption' => 'Tägliche Benachrichtigung',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'DailyNotificationMonday',
                            'caption' => 'Mo'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'DailyNotificationTuesday',
                            'caption' => 'Di'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'DailyNotificationWednesday',
                            'caption' => 'Mi'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'DailyNotificationThursday',
                            'caption' => 'Do'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'DailyNotificationFriday',
                            'caption' => 'Fr'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'DailyNotificationSaturday',
                            'caption' => 'Sa'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'DailyNotificationSunday',
                            'caption' => 'So'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ]
                    ]
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'DailyNotificationTime',
                    'caption' => 'Benachrichtigung um (Uhrzeit)'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'DailyNotificationAlwaysResetCriticalVariables',
                    'caption' => 'Kritische Melder immer zurücksetzen, auch an nicht ausgewählten Tagen'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Daily notification

                [
                    'type'    => 'Label',
                    'caption' => 'Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "DailyNotificationConfigurationButton", "ID " . $DailyNotification["ID"] . " konfigurieren", $DailyNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'UpdateOverdueIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'UpdateOverdueTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s Aktualisierung überfällig',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'UpdateOverdueDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'LowBatteryIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'LowBatteryTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️  %1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'LowBatteryDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'BatteryOKIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'BatteryOKTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s Batterie OK',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'BatteryOKDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'MonitoringDisabledSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'MonitoringDisabledLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'UseMonitoringDisabled',
                            'width'   => '210px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'MonitoringDisabledIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'MonitoringDisabledTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'MonitoringDisabledMessageText',
                            'width'   => '200px',
                            'add'     => '❌  %1$s Überwachung deaktiviert',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseMonitoringDisabledTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'MonitoringDisabledDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ]
                    ],
                    'values' => $dailyNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'DailyNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Daily push notification

                [
                    'type'    => 'Label',
                    'caption' => 'Push-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyPushNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "DailyPushNotificationConfigurationButton", "ID " . $DailyPushNotification["ID"] . " konfigurieren", $DailyPushNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'UpdateOverdueTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s Aktualisierung überfällig',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'UpdateOverdueSound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'UpdateOverdueTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'LowBatteryTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️  %1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'LowBatterySound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'LowBatteryTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'BatteryOKTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s Batterie OK',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'BatteryOKSound',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'BatteryOKTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'MonitoringDisabledSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'MonitoringDisabledLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'UseMonitoringDisabled',
                            'width'   => '210px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'MonitoringDisabledTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'MonitoringDisabledMessageText',
                            'width'   => '200px',
                            'add'     => '❌  %1$s Überwachung deaktiviert',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseMonitoringDisabledTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'MonitoringDisabledSound',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'MonitoringDisabledTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ]
                    ],
                    'values' => $dailyPushNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'DailyPushNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Daily email notification

                [
                    'type'    => 'Label',
                    'caption' => 'E-Mail',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyMailerNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Mailer',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "DailyNotificationMailerConfigurationButton", "ID " . $DailyNotificationMailer["ID"] . " konfigurieren", $DailyNotificationMailer["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::MAILER_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Betreff',
                            'name'    => 'Subject',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder (Standort)',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseUpdateOverdueVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseUpdateOverdueBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseLowBatteryVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseLowBatteryBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseBatteryOKVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseBatteryOKBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'MonitoringDisabledSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'MonitoringDisabledLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'UseMonitoringDisabled',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'MonitoringDisabledMessageText',
                            'width'   => '200px',
                            'add'     => '❌  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseMonitoringDisabledTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseMonitoringDisabledVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseMonitoringDisabledBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
                    ],
                    'values' => $dailyNotificationMailerValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "Mailer");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'DailyNotificationMailerConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ]
            ]
        ];

        ##### Weekly notification

        //Weekly notification
        $weeklyNotificationValues = [];
        foreach (json_decode($this->ReadPropertyString('WeeklyNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $weeklyNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Weekly push notification
        $weeklyPushNotificationValues = [];
        foreach (json_decode($this->ReadPropertyString('WeeklyPushNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $weeklyPushNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Weekly mailer notification
        $weeklyNotificationMailerValues = [];
        foreach (json_decode($this->ReadPropertyString('WeeklyMailerNotification'), true) as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $weeklyNotificationMailerValues[] = ['rowColor' => $rowColor];
        }

        ##### Element: Weekly notification

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel6',
            'caption' => 'Wöchentliche Benachrichtigung',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Select',
                            'name'    => 'WeeklyNotificationDay',
                            'caption' => 'Benachrichtigung am (Wochentag)',
                            'options' => [
                                [
                                    'caption' => 'Sonntag',
                                    'value'   => 0
                                ],
                                [
                                    'caption' => 'Montag',
                                    'value'   => 1
                                ],
                                [
                                    'caption' => 'Dienstag',
                                    'value'   => 2
                                ],
                                [
                                    'caption' => 'Mittwoch',
                                    'value'   => 3
                                ],
                                [
                                    'caption' => 'Donnerstag',
                                    'value'   => 4
                                ],
                                [
                                    'caption' => 'Freitag',
                                    'value'   => 5
                                ],
                                [
                                    'caption' => 'Samstag',
                                    'value'   => 6
                                ]
                            ]
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'SelectTime',
                            'name'    => 'WeeklyNotificationTime',
                            'caption' => 'Benachrichtigung um (Uhrzeit)'
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Weekly notification

                [
                    'type'    => 'Label',
                    'caption' => 'Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "WeeklyNotificationConfigurationButton", "ID " . $WeeklyNotification["ID"] . " konfigurieren", $WeeklyNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'UpdateOverdueIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'UpdateOverdueTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s Aktualisierung überfällig',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'UpdateOverdueDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'LowBatteryIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'LowBatteryTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️  %1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'LowBatteryDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'BatteryOKIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'BatteryOKTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s Batterie OK',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'BatteryOKDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'MonitoringDisabledSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'MonitoringDisabledLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'UseMonitoringDisabled',
                            'width'   => '210px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'MonitoringDisabledIcon',
                            'width'   => '200px',
                            'add'     => 'Battery',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung',
                            'name'    => 'MonitoringDisabledTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'MonitoringDisabledMessageText',
                            'width'   => '200px',
                            'add'     => '❌  %1$s Überwachung deaktiviert',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseMonitoringDisabledTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'MonitoringDisabledDisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ]
                    ],
                    'values' => $weeklyNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'WeeklyNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Weekly push notification

                [
                    'type'    => 'Label',
                    'caption' => 'Push-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyPushNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "WeeklyPushNotificationConfigurationButton", "ID " . $WeeklyPushNotification["ID"] . " konfigurieren", $WeeklyPushNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'UpdateOverdueTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s Aktualisierung überfällig',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'UpdateOverdueSound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'UpdateOverdueTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'LowBatteryTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️  %1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'LowBatterySound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'LowBatteryTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'BatteryOKTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s Batterie OK',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'BatteryOKSound',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'BatteryOKTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'MonitoringDisabledSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'MonitoringDisabledLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'UseMonitoringDisabled',
                            'width'   => '210px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'MonitoringDisabledTitle',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext (maximal 256 Zeichen)',
                            'name'    => 'MonitoringDisabledMessageText',
                            'width'   => '200px',
                            'add'     => '❌  %1$s Überwachung deaktiviert',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseMonitoringDisabledTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'MonitoringDisabledSound',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'MonitoringDisabledTargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ]
                    ],
                    'values' => $weeklyPushNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'WeeklyPushNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],

                ### Weekly email notification

                [
                    'type'    => 'Label',
                    'caption' => 'E-Mail',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyMailerNotification',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Mailer',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "WeeklyNotificationMailerConfigurationButton", "ID " . $WeeklyNotificationMailer["ID"] . " konfigurieren", $WeeklyNotificationMailer["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::MAILER_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Betreff',
                            'name'    => 'Subject',
                            'width'   => '350px',
                            'add'     => 'Batteriemelder (Standort)',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'UpdateOverdueSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UpdateOverdueLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Aktualisierung überfällig',
                            'name'    => 'UseUpdateOverdue',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'UpdateOverdueMessageText',
                            'width'   => '200px',
                            'add'     => '❗  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseUpdateOverdueTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseUpdateOverdueVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseUpdateOverdueBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'LowBatterySpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'LowBatteryLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie schwach',
                            'name'    => 'UseLowBattery',
                            'width'   => '160px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'LowBatteryMessageText',
                            'width'   => '200px',
                            'add'     => '⚠️  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseLowBatteryTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseLowBatteryVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseLowBatteryBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'BatteryOKSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'BatteryOKLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Batterie OK',
                            'name'    => 'UseBatteryOK',
                            'width'   => '120px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'BatteryOKMessageText',
                            'width'   => '200px',
                            'add'     => '🟢  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseBatteryOKTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseBatteryOKVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseBatteryOKBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'MonitoringDisabledSpacer',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'MonitoringDisabledLabel',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'bold'   => true,
                                'italic' => true
                            ]
                        ],
                        [
                            'caption' => 'Überwachung deaktiviert',
                            'name'    => 'UseMonitoringDisabled',
                            'width'   => '210px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Meldungstext',
                            'name'    => 'MonitoringDisabledMessageText',
                            'width'   => '200px',
                            'add'     => '❌  %1$s',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseMonitoringDisabledTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Melder ID',
                            'name'    => 'UseMonitoringDisabledVariableID',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Batterietyp',
                            'name'    => 'UseMonitoringDisabledBatteryType',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
                    ],
                    'values' => $weeklyNotificationMailerValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "Mailer");'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'WeeklyNotificationMailerConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ]
                    ]
                ]
            ]
        ];

        ##### Element: Visualisation

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel7',
            'caption' => 'Visualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableStatus',
                    'caption' => 'Status'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableTriggeringDetector',
                    'caption' => 'Auslösender Melder'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableBatteryReplacement',
                    'caption' => 'Batteriewechsel ID'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLastUpdate',
                    'caption' => 'Letzte Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableUpdateStatus',
                    'caption' => 'Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableBatteryList',
                    'caption' => 'Batterieliste'
                ]
            ]
        ];

        ########## Actions

        $form['actions'][] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Tägliche Benachrichtigung versenden',
                        'popup'   => [
                            'caption' => 'Tägliche Benachrichtigung wirklich versenden?',
                            'items'   => [
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Versenden',
                                    'onClick' => self::MODULE_PREFIX . '_ExecuteDailyNotification($id, false, false);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die tägliche Benachrichtigung wurde versendet!");'
                                ]
                            ]

                        ]
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Wöchentliche Benachrichtigung versenden',
                        'popup'   => [
                            'caption' => 'Wöchentliche Benachrichtigung wirklich versenden?',
                            'items'   => [
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Versenden',
                                    'onClick' => self::MODULE_PREFIX . '_ExecuteWeeklyNotification($id, false, false);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die tägliche Benachrichtigung wurde versendet!");'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Alle Benachrichtigungslisten zurücksetzen',
                        'popup'   => [
                            'caption' => 'Alle Benachrichtigungslisten wirklich zurücksetzen?',
                            'items'   => [
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Zurücksetzen',
                                    'onClick' => self::MODULE_PREFIX . '_ResetNotificationLists($id);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Listen wurden zurückgesetzt, bitte Konfiguration neu laden!");'
                                ]
                            ]
                        ]
                    ],
                ]
            ];

        //Determine variables
        $form['actions'][] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Variablen ermitteln',
                        'popup'   => [
                            'caption' => 'Variablen wirklich automatisch ermitteln und hinzufügen?',
                            'items'   => [
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Ermitteln',
                                    'onClick' => self::MODULE_PREFIX . '_DetermineTriggerVariables($id, $SelectIdents, $ObjectIdents);'
                                ],
                                [
                                    'type'    => 'ProgressBar',
                                    'name'    => 'DetermineVariableProgress',
                                    'caption' => 'Fortschritt',
                                    'minimum' => 0,
                                    'maximum' => 100,
                                    'visible' => false
                                ],
                                [
                                    'type'    => 'Label',
                                    'name'    => 'DetermineVariableProgressInfo',
                                    'caption' => '',
                                    'visible' => false
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'    => 'Select',
                        'name'    => 'SelectIdents',
                        'options' => [
                            [
                                'caption' => 'Benutzerdefiniert',
                                'value'   => ''
                            ],
                            [
                                'caption' => 'LOWBAT',
                                'value'   => 'LOWBAT'
                            ],
                            [
                                'caption' => 'LOW_BAT',
                                'value'   => 'LOW_BAT'
                            ]
                        ],
                        'value' => ''
                    ],
                    [
                        'type'    => 'ValidationTextBox',
                        'name'    => 'ObjectIdents',
                        'caption' => 'Identifikator',
                        'value'   => 'LOWBAT, LOW_BAT'
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Variablenprofil zuweisen',
                        'popup'   => [
                            'caption' => 'Variablenprofile wirklich zuweisen?',
                            'items'   => [
                                [
                                    'type'    => 'CheckBox',
                                    'name'    => 'OverrideProfiles',
                                    'caption' => 'Bestehende Variablenprofile überschreiben',
                                    'value'   => true
                                ],
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Zuweisen',
                                    'onClick' => self::MODULE_PREFIX . '_AssignVariableProfile($id, $OverrideProfiles);'
                                ],
                                [
                                    'type'    => 'ProgressBar',
                                    'name'    => 'VariableProfileProgress',
                                    'caption' => 'Fortschritt',
                                    'minimum' => 0,
                                    'maximum' => 100,
                                    'visible' => false
                                ],
                                [
                                    'type'    => 'Label',
                                    'name'    => 'VariableProfileProgressInfo',
                                    'caption' => '',
                                    'visible' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        //Test center
        $form['actions'][] =
            [
                'type' => 'TestCenter'
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        ##### Immediate notification

        //Update overdue
        $updateOverdueVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusUpdateOverdue'), true);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $updateOverdueVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFC0C0']; //red
                }
            }
        }

        //Low battery
        $lowBatteryVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusLowBattery'), true);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $lowBatteryVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFFFC0']; //yellow
                }
            }
        }

        //Normal battery
        $normalBatteryVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusNormal'), true);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $normalBatteryVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#C0FFC0']; //light green
                }
            }
        }

        ##### Daily notification

        //Update overdue
        $dailyUpdateOverdueVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusUpdateOverdue'), true);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $dailyUpdateOverdueVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFC0C0']; //red
                }
            }
        }

        //Low battery
        $dailyLowBatteryVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $dailyLowBatteryVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFFFC0']; //yellow
                }
            }
        }

        ##### Weekly notification

        //Update overdue
        $weeklyUpdateOverdueVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusUpdateOverdue'), true);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $weeklyUpdateOverdueVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFC0C0']; //red
                }
            }
        }

        //Low battery
        $weeklyLowBatteryVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $weeklyLowBatteryVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFFFC0']; //yellow
                }
            }
        }

        ##### Registered references and messages

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID' => $reference,
                'Name'     => $name,
                'rowColor' => $rowColor];
        }

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $rowColor = '#C0FFC0'; //light green
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        //Developer area
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Entwicklerbereich',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Auslöser',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'SelectCategory',
                            'name'    => 'LinkCategory',
                            'caption' => 'Kategorie',
                            'width'   => '610px'
                        ],
                        [
                            'type'    => 'PopupButton',
                            'caption' => 'Verknüpfung erstellen',
                            'popup'   => [
                                'caption' => 'Variablenverknüpfungen wirklich erstellen?',
                                'items'   => [
                                    [
                                        'type'    => 'Button',
                                        'caption' => 'Erstellen',
                                        'onClick' => self::MODULE_PREFIX . '_CreateVariableLinks($id, $LinkCategory);'
                                    ],
                                    [
                                        'type'    => 'ProgressBar',
                                        'name'    => 'VariableLinkProgress',
                                        'caption' => 'Fortschritt',
                                        'minimum' => 0,
                                        'maximum' => 100,
                                        'visible' => false
                                    ],
                                    [
                                        'type'    => 'Label',
                                        'name'    => 'VariableLinkProgressInfo',
                                        'caption' => '',
                                        'visible' => false
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Sofortige Benachrichtigung',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediateNotificationListDeviceStatusUpdateOverdue',
                    'caption'  => 'Aktualisierung überfällig',
                    'rowCount' => 5,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'ID',
                            'caption' => 'Variable ID',
                            'width'   => '110px'
                        ],
                        [
                            'name'    => 'Name',
                            'caption' => 'Name',
                            'width'   => '350px'
                        ],
                        [
                            'name'    => 'Comment',
                            'caption' => 'Bemerkung',
                            'width'   => '250px'
                        ],
                        [
                            'name'    => 'BatteryType',
                            'caption' => 'Batterietyp',
                            'width'   => '200px'
                        ],
                        [
                            'name'    => 'Timestamp',
                            'caption' => 'Datum, Uhrzeit',
                            'width'   => '160px'
                        ]
                    ],
                    'values' => $updateOverdueVariables
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Zurücksetzen',
                    'popup'   => [
                        'caption' => 'Liste wirklich zurücksetzen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Zurücksetzen',
                                'onClick' => self::MODULE_PREFIX . '_ResetAttribute($id, "ImmediateNotificationListDeviceStatusUpdateOverdue");' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Liste wurde zurückgesetzt, bitte Konfiguration neu laden!");'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediateNotificationListDeviceStatusLowBattery',
                    'caption'  => 'Batterie schwach',
                    'rowCount' => 5,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'ID',
                            'caption' => 'Variable ID',
                            'width'   => '110px'
                        ],
                        [
                            'name'    => 'Name',
                            'caption' => 'Name',
                            'width'   => '350px'
                        ],
                        [
                            'name'    => 'Comment',
                            'caption' => 'Bemerkung',
                            'width'   => '250px'
                        ],
                        [
                            'name'    => 'BatteryType',
                            'caption' => 'Batterietyp',
                            'width'   => '200px'
                        ],
                        [
                            'name'    => 'Timestamp',
                            'caption' => 'Datum, Uhrzeit',
                            'width'   => '160px'
                        ]
                    ],
                    'values' => $lowBatteryVariables
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Zurücksetzen',
                    'popup'   => [
                        'caption' => 'Liste wirklich zurücksetzen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Zurücksetzen',
                                'onClick' => self::MODULE_PREFIX . '_ResetAttribute($id, "ImmediateNotificationListDeviceStatusLowBattery");' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Liste wurde zurückgesetzt, bitte Konfiguration neu laden!");'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediateNotificationListDeviceStatusNormal',
                    'caption'  => 'Batterie OK',
                    'rowCount' => 5,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'ID',
                            'caption' => 'Variable ID',
                            'width'   => '110px'
                        ],
                        [
                            'name'    => 'Name',
                            'caption' => 'Name',
                            'width'   => '350px'
                        ],
                        [
                            'name'    => 'Comment',
                            'caption' => 'Bemerkung',
                            'width'   => '250px'
                        ],
                        [
                            'name'    => 'BatteryType',
                            'caption' => 'Batterietyp',
                            'width'   => '200px'
                        ],
                        [
                            'name'    => 'Timestamp',
                            'caption' => 'Datum, Uhrzeit',
                            'width'   => '160px'
                        ]
                    ],
                    'values' => $normalBatteryVariables
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Zurücksetzen',
                    'popup'   => [
                        'caption' => 'Liste wirklich zurücksetzen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Zurücksetzen',
                                'onClick' => self::MODULE_PREFIX . '_ResetAttribute($id, "ImmediateNotificationListDeviceStatusNormal");' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Liste wurde zurückgesetzt, bitte Konfiguration neu laden!");'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Tägliche Benachrichtigung',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyNotificationListDeviceStatusUpdateOverdue',
                    'caption'  => 'Aktualisierung überfällig',
                    'rowCount' => 5,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'ID',
                            'caption' => 'Variable ID',
                            'width'   => '110px'
                        ],
                        [
                            'name'    => 'Name',
                            'caption' => 'Name',
                            'width'   => '350px'
                        ],
                        [
                            'name'    => 'Comment',
                            'caption' => 'Bemerkung',
                            'width'   => '250px'
                        ],
                        [
                            'name'    => 'BatteryType',
                            'caption' => 'Batterietyp',
                            'width'   => '200px'
                        ],
                        [
                            'name'    => 'Timestamp',
                            'caption' => 'Datum, Uhrzeit',
                            'width'   => '160px'
                        ]
                    ],
                    'values' => $dailyUpdateOverdueVariables
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Zurücksetzen',
                    'popup'   => [
                        'caption' => 'Liste wirklich zurücksetzen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Zurücksetzen',
                                'onClick' => self::MODULE_PREFIX . '_ResetAttribute($id, "DailyNotificationListDeviceStatusUpdateOverdue");' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Liste wurde zurückgesetzt!, bitte Konfiguration neu laden");'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyNotificationListDeviceStatusLowBattery',
                    'caption'  => 'Batterie schwach',
                    'rowCount' => 5,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'ID',
                            'caption' => 'Variable ID',
                            'width'   => '110px'
                        ],
                        [
                            'name'    => 'Name',
                            'caption' => 'Name',
                            'width'   => '350px'
                        ],
                        [
                            'name'    => 'Comment',
                            'caption' => 'Bemerkung',
                            'width'   => '250px'
                        ],
                        [
                            'name'    => 'BatteryType',
                            'caption' => 'Batterietyp',
                            'width'   => '200px'
                        ],
                        [
                            'name'    => 'Timestamp',
                            'caption' => 'Datum, Uhrzeit',
                            'width'   => '160px'
                        ]
                    ],
                    'values' => $dailyLowBatteryVariables
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Zurücksetzen',
                    'popup'   => [
                        'caption' => 'Liste wirklich zurücksetzen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Zurücksetzen',
                                'onClick' => self::MODULE_PREFIX . '_ResetAttribute($id, "DailyNotificationListDeviceStatusLowBattery");' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Liste wurde zurückgesetzt!, bitte Konfiguration neu laden");'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Wöchentliche Benachrichtigung',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyNotificationListDeviceStatusUpdateOverdue',
                    'caption'  => 'Aktualisierung überfällig',
                    'rowCount' => 5,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'ID',
                            'caption' => 'Variable ID',
                            'width'   => '110px'
                        ],
                        [
                            'name'    => 'Name',
                            'caption' => 'Name',
                            'width'   => '350px'
                        ],
                        [
                            'name'    => 'Comment',
                            'caption' => 'Bemerkung',
                            'width'   => '250px'
                        ],
                        [
                            'name'    => 'BatteryType',
                            'caption' => 'Batterietyp',
                            'width'   => '200px'
                        ],
                        [
                            'name'    => 'Timestamp',
                            'caption' => 'Datum, Uhrzeit',
                            'width'   => '160px'
                        ]
                    ],
                    'values' => $weeklyUpdateOverdueVariables
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Zurücksetzen',
                    'popup'   => [
                        'caption' => 'Liste wirklich zurücksetzen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Zurücksetzen',
                                'onClick' => self::MODULE_PREFIX . '_ResetAttribute($id, "WeeklyNotificationListDeviceStatusUpdateOverdue");' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Liste wurde zurückgesetzt, bitte Konfiguration neu laden!");'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyNotificationListDeviceStatusLowBattery',
                    'caption'  => 'Batterie schwach',
                    'rowCount' => 5,
                    'sort'     => [
                        'column'    => 'Name',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'ID',
                            'caption' => 'Variable ID',
                            'width'   => '110px'
                        ],
                        [
                            'name'    => 'Name',
                            'caption' => 'Name',
                            'width'   => '350px'
                        ],
                        [
                            'name'    => 'Comment',
                            'caption' => 'Bemerkung',
                            'width'   => '250px'
                        ],
                        [
                            'name'    => 'BatteryType',
                            'caption' => 'Batterietyp',
                            'width'   => '200px'
                        ],
                        [
                            'name'    => 'Timestamp',
                            'caption' => 'Datum, Uhrzeit',
                            'width'   => '160px'
                        ]
                    ],
                    'values' => $weeklyLowBatteryVariables
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Zurücksetzen',
                    'popup'   => [
                        'caption' => 'Liste wirklich zurücksetzen?',
                        'items'   => [
                            [
                                'type'    => 'Button',
                                'caption' => 'Zurücksetzen',
                                'onClick' => self::MODULE_PREFIX . '_ResetAttribute($id, "WeeklyNotificationListDeviceStatusLowBattery");' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Liste wurde zurückgesetzt, bitte Konfiguration neu laden!");'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'caption'  => 'Registrierte Referenzen',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'caption'  => 'Registrierte Nachrichten',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Dummy info message
        $form['actions'][] =
            [
                'type'    => 'PopupAlert',
                'name'    => 'InfoMessage',
                'visible' => false,
                'popup'   => [
                    'closeCaption' => 'OK',
                    'items'        => [
                        [
                            'type'    => 'Label',
                            'name'    => 'InfoMessageLabel',
                            'caption' => '',
                            'visible' => true
                        ]
                    ]
                ]
            ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => $module['ModuleName'] . ' ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }
}