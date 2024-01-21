<?php

/**
 * @project       Batteriemelder/Batteriemelder/helper/
 * @file          BATM_ConfigurationForm.php
 * @author        Ulrich Bittner
 * @copyright     2023, 2024 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */

declare(strict_types=1);

trait BATM_ConfigurationForm
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
        for ($i = 1; $i <= 9; $i++) {
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

    public function ModifyActualVariableStatesVariableButton(string $Field, int $VariableID): void
    {
        $state = false;
        if ($VariableID > 1 && @IPS_ObjectExists($VariableID)) { //0 = main category, 1 = none
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $VariableID . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $VariableID);
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

        //Status designations
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel2',
            'caption' => 'Statusbezeichnungen',
            'items'   => [
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'StatusTextAlarm',
                    'caption' => 'Bezeichnung für Alarm'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'StatusTextOK',
                    'caption' => 'Bezeichnung für OK'
                ]
            ]
        ];

        //Battery list options
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel3',
            'caption' => 'Listenoptionen',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type' => 'CheckBox',
                            'name' => 'EnableLowBattery',
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'LowBatteryStatusText',
                            'caption' => 'Batterie schwach'
                        ]
                    ]
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type' => 'CheckBox',
                            'name' => 'EnableBatteryOK'
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'BatteryOKStatusText',
                            'caption' => 'Batterie OK'
                        ]
                    ]
                ]
            ]
        ];

        //Trigger list
        $triggerListValues = [];
        $variableProfileListValues = [];
        $variableLinksListValues = [];
        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        $amountVariables = count($variables);
        $amountRows = count($variables) + 1;
        if ($amountRows == 1) {
            $amountRows = 3;
        }
        foreach ($variables as $variable) {
            $id = 0;
            $rowColor = '#FFC0C0'; //red
            //Primary condition
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) {
                            $rowColor = '#C0FFC0'; //light green
                            if (!$variable['Use']) {
                                $rowColor = '#DFDFDF'; //grey
                            }
                        }
                    }
                }
            }
            $triggerListValues[] = ['rowColor' => $rowColor];
            $variableProfileListValues[] = ['SensorID' => $id, 'Designation' => $variable['Designation'], 'Comment' => $variable['Comment']];
            $variableLinksListValues[] = ['SensorID' => $id, 'Designation' => $variable['Designation'], 'Comment' => $variable['Comment']];
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel4',
            'caption' => 'Auslöser',
            'items'   => [
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Variablen ermitteln',
                    'popup'   => [
                        'caption' => 'Variablen wirklich automatisch ermitteln und hinzufügen?',
                        'items'   => [
                            [
                                'type'    => 'Select',
                                'name'    => 'VariableDeterminationType',
                                'caption' => 'Auswahl',
                                'options' => [
                                    [
                                        'caption' => 'Profil auswählen',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Ident: LOWBAT',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Ident: LOW_BAT',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Ident: LOWBAT, LOW_BAT',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'Ident: Benutzerdefiniert',
                                        'value'   => 4
                                    ]
                                ],
                                'value'    => 0,
                                'onChange' => self::MODULE_PREFIX . '_CheckVariableDeterminationValue($id, $VariableDeterminationType);'
                            ],
                            [
                                'type'    => 'SelectProfile',
                                'name'    => 'ProfileSelection',
                                'caption' => 'Profil',
                                'visible' => true
                            ],
                            [
                                'type'    => 'ValidationTextBox',
                                'name'    => 'VariableDeterminationValue',
                                'caption' => 'Identifikator',
                                'visible' => false
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Ermitteln',
                                'onClick' => self::MODULE_PREFIX . '_DetermineVariables($id, $VariableDeterminationType, $VariableDeterminationValue, $ProfileSelection);'
                            ],
                            [
                                'type'    => 'ProgressBar',
                                'name'    => 'VariableDeterminationProgress',
                                'caption' => 'Fortschritt',
                                'minimum' => 0,
                                'maximum' => 100,
                                'visible' => false
                            ],
                            [
                                'type'    => 'Label',
                                'name'    => 'VariableDeterminationProgressInfo',
                                'caption' => '',
                                'visible' => false
                            ],
                            [
                                'type'     => 'List',
                                'name'     => 'DeterminedVariableList',
                                'caption'  => 'Variablen',
                                'visible'  => false,
                                'rowCount' => 15,
                                'delete'   => true,
                                'sort'     => [
                                    'column'    => 'Location',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'caption' => 'Übernehmen',
                                        'name'    => 'Use',
                                        'width'   => '100px',
                                        'add'     => false,
                                        'edit'    => [
                                            'type' => 'CheckBox'
                                        ]
                                    ],
                                    [
                                        'name'    => 'ID',
                                        'caption' => 'ID',
                                        'width'   => '80px',
                                        'add'     => ''
                                    ],
                                    [
                                        'caption' => 'Objektbaum',
                                        'name'    => 'Location',
                                        'width'   => '800px',
                                        'add'     => ''
                                    ],
                                ]
                            ],
                            [
                                'type'    => 'CheckBox',
                                'name'    => 'OverwriteVariableProfiles',
                                'caption' => 'Variablenprofile überschreiben',
                                'visible' => false,
                                'value'   => true
                            ],
                            [
                                'type'    => 'Button',
                                'name'    => 'ApplyPreTriggerValues',
                                'caption' => 'Übernehmen',
                                'visible' => false,
                                'onClick' => self::MODULE_PREFIX . '_ApplyDeterminedVariables($id, $DeterminedVariableList, $OverwriteVariableProfiles);'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Aktueller Status',
                    'popup'   => [
                        'caption' => 'Aktueller Status',
                        'items'   => [
                            [
                                'type'     => 'List',
                                'name'     => 'ActualVariableStates',
                                'caption'  => 'Variablen',
                                'add'      => false,
                                'rowCount' => 1,
                                'sort'     => [
                                    'column'    => 'ActualStatus',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'name'    => 'ActualStatus',
                                        'caption' => 'Aktueller Status',
                                        'width'   => '200px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'SensorID',
                                        'caption' => 'ID',
                                        'width'   => '80px',
                                        'onClick' => self::MODULE_PREFIX . '_ModifyActualVariableStatesVariableButton($id, "ActualVariableStatesConfigurationButton", $ActualVariableStates["SensorID"]);',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'Designation',
                                        'caption' => 'Name',
                                        'width'   => '400px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'Comment',
                                        'caption' => 'Bemerkung',
                                        'width'   => '400px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'BatteryType',
                                        'caption' => 'Batterietyp',
                                        'width'   => '200px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'LastBatteryReplacement',
                                        'caption' => 'Letzter Batteriewechsel',
                                        'width'   => '200px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'LastUpdate',
                                        'caption' => 'Letzte Aktualisierung',
                                        'width'   => '200px',
                                        'save'    => false
                                    ]
                                ]
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'ActualVariableStatesConfigurationButton',
                                'caption'  => 'Bearbeiten',
                                'visible'  => false,
                                'objectID' => 0
                            ]
                        ]
                    ],
                    'onClick' => self::MODULE_PREFIX . '_GetActualVariableStates($id);'
                ],
                [
                    'type'     => 'List',
                    'name'     => 'TriggerList',
                    'caption'  => 'Auslöser',
                    'rowCount' => $amountRows,
                    'add'      => true,
                    'delete'   => true,
                    'sort'     => [
                        'column'    => 'Designation',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
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
                            'caption' => 'Name',
                            'name'    => 'Designation',
                            'width'   => '400px',
                            'add'     => '',
                            'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $TriggerList["PrimaryCondition"]);',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Bemerkung',
                            'name'    => 'Comment',
                            'width'   => '300px',
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
                            'width'   => '300px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Mehrfachauslösung',
                            'name'    => 'UseMultipleAlerts',
                            'width'   => '200px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Primäre Bedingung ',
                            'name'    => 'PrimaryCondition',
                            'width'   => '1000px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'SelectCondition'
                            ]
                        ],
                        [
                            'caption' => 'Letzter Batteriewechsel',
                            'name'    => 'LastBatteryReplacement',
                            'width'   => '200px',
                            'add'     => '{"year":0,"month":0,"day":0}',
                            'edit'    => [
                                'type' => 'SelectDate'
                            ]
                        ]
                    ],
                    'values' => $triggerListValues,
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Anzahl Auslöser: ' . $amountVariables
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Variablenprofil zuweisen',
                    'popup'   => [
                        'caption' => 'Variablenprofil wirklich automatisch zuweisen?',
                        'items'   => [
                            [
                                'type'     => 'List',
                                'name'     => 'VariableProfileList',
                                'caption'  => 'Variablen',
                                'add'      => false,
                                'rowCount' => $amountVariables,
                                'sort'     => [
                                    'column'    => 'Designation',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'caption' => 'Auswahl',
                                        'name'    => 'Use',
                                        'width'   => '100px',
                                        'add'     => true,
                                        'edit'    => [
                                            'type' => 'CheckBox'
                                        ]
                                    ],
                                    [
                                        'caption' => 'Profil umkehren',
                                        'name'    => 'UseReversedProfile',
                                        'width'   => '150px',
                                        'add'     => false,
                                        'edit'    => [
                                            'type' => 'CheckBox'
                                        ]
                                    ],
                                    [
                                        'name'    => 'SensorID',
                                        'caption' => 'ID',
                                        'width'   => '80px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'Designation',
                                        'caption' => 'Name',
                                        'width'   => '400px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'Comment',
                                        'caption' => 'Bemerkung',
                                        'width'   => '400px',
                                        'save'    => false
                                    ]
                                ],
                                'values' => $variableProfileListValues,
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Zuweisen',
                                'onClick' => self::MODULE_PREFIX . '_AssignVariableProfile($id, $VariableProfileList);'
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
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Verknüpfung erstellen',
                    'popup'   => [
                        'caption' => 'Variablenverknüpfungen wirklich erstellen?',
                        'items'   => [
                            [
                                'type'    => 'SelectCategory',
                                'name'    => 'LinkCategory',
                                'caption' => 'Kategorie',
                                'width'   => '610px'
                            ],
                            [
                                'type'     => 'List',
                                'name'     => 'VariableLinkList',
                                'caption'  => 'Variablen',
                                'add'      => false,
                                'rowCount' => $amountVariables,
                                'sort'     => [
                                    'column'    => 'Designation',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'caption' => 'Auswahl',
                                        'name'    => 'Use',
                                        'width'   => '100px',
                                        'add'     => false,
                                        'edit'    => [
                                            'type' => 'CheckBox'
                                        ]
                                    ],
                                    [
                                        'name'    => 'SensorID',
                                        'caption' => 'ID',
                                        'width'   => '80px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'Designation',
                                        'caption' => 'Name',
                                        'width'   => '400px',
                                        'save'    => false
                                    ],
                                    [
                                        'name'    => 'Comment',
                                        'caption' => 'Bemerkung',
                                        'width'   => '400px',
                                        'save'    => false
                                    ]
                                ],
                                'values' => $variableLinksListValues,
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Erstellen',
                                'onClick' => self::MODULE_PREFIX . '_CreateVariableLinks($id, $LinkCategory, $VariableLinkList);'
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

        //Automatic status update
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel5',
            'caption' => 'Aktualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'AutomaticStatusUpdate',
                    'caption' => 'Automatische Aktualisierung'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'StatusUpdateInterval',
                    'caption' => 'Intervall',
                    'suffix'  => 'Sekunden'
                ]
            ]
        ];

        //Immediate notification
        $immediateNotificationValues = [];
        $immediateNotification = json_decode($this->ReadPropertyString('ImmediateNotification'), true);
        $amountImmediateNotification = count($immediateNotification) + 1;
        if ($amountImmediateNotification == 1) {
            $amountImmediateNotification = 3;
        }
        foreach ($immediateNotification as $element) {
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
        $immediatePushNotification = json_decode($this->ReadPropertyString('ImmediatePushNotification'), true);
        $amountImmediatePushNotification = count($immediatePushNotification) + 1;
        if ($amountImmediatePushNotification == 1) {
            $amountImmediatePushNotification = 3;
        }
        foreach ($immediatePushNotification as $element) {
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

        //Immediate post notification
        $immediatePostNotificationValues = [];
        $immediatePostNotification = json_decode($this->ReadPropertyString('ImmediatePostNotification'), true);
        $amountImmediatePostNotification = count($immediatePostNotification) + 1;
        if ($amountImmediatePostNotification == 1) {
            $amountImmediatePostNotification = 3;
        }
        foreach ($immediatePostNotification as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $immediatePostNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Immediate mailer notification
        $immediateNotificationMailerValues = [];
        $immediateMailerNotification = json_decode($this->ReadPropertyString('ImmediateMailerNotification'), true);
        $amountImmediateMailerNotification = count($immediateMailerNotification) + 1;
        if ($amountImmediateMailerNotification == 1) {
            $amountImmediateMailerNotification = 3;
        }
        foreach ($immediateMailerNotification as $element) {
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

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel6',
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
                //Immediate notification
                [
                    'type'    => 'Label',
                    'caption' => 'Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediateNotification',
                    'rowCount' => $amountImmediateNotification,
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                //Immediate push notification
                [
                    'type'    => 'Label',
                    'caption' => 'Push-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediatePushNotification',
                    'rowCount' => $amountImmediatePushNotification,
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                //Immediate post notification
                [
                    'type'    => 'Label',
                    'caption' => 'Post-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediatePostNotification',
                    'rowCount' => $amountImmediatePostNotification,
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
                            'caption' => 'Kachel Visualisierung',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "ImmediatePostNotificationConfigurationButton", "ID " . $ImmediatePostNotification["ID"] . " konfigurieren", $ImmediatePostNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::TILE_VISUALISATION_MODULE_GUID
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'caption' => 'Sound',
                            'name'    => 'LowBatterySound',
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
                            'caption' => 'Ziel ID',
                            'name'    => 'LowBatteryTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                            'caption' => 'Ziel ID',
                            'name'    => 'BatteryOKTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ]
                    ],
                    'values' => $immediatePostNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "TileVisualisation");'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'ImmediatePostNotificationConfigurationButton',
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
                //Immediate email notification
                [
                    'type'    => 'Label',
                    'caption' => 'E-Mail',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'ImmediateMailerNotification',
                    'rowCount' => $amountImmediateMailerNotification,
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
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "ImmediateNotificationMailerConfigurationButton", "ID " . $ImmediateMailerNotification["ID"] . " konfigurieren", $ImmediateMailerNotification["ID"]);',
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
                            'add'     => '⚠️%1$s',
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
                            'add'     => '🟢 %1$s',
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

        //Daily notification
        $dailyNotificationValues = [];
        $dailyNotification = json_decode($this->ReadPropertyString('DailyNotification'), true);
        $amountDailyNotification = count($dailyNotification) + 1;
        if ($amountDailyNotification == 1) {
            $amountDailyNotification = 3;
        }
        foreach ($dailyNotification as $element) {
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
        $dailyPushNotification = json_decode($this->ReadPropertyString('DailyPushNotification'), true);
        $amountDailyPushNotification = count($dailyPushNotification) + 1;
        if ($amountDailyPushNotification == 1) {
            $amountDailyPushNotification = 3;
        }
        foreach ($dailyPushNotification as $element) {
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

        //Daily post notification
        $dailyPostNotificationValues = [];
        $dailyPostNotification = json_decode($this->ReadPropertyString('DailyPostNotification'), true);
        $amountDailyPostNotification = count($dailyPostNotification) + 1;
        if ($amountDailyPostNotification == 1) {
            $amountDailyPostNotification = 3;
        }
        foreach ($dailyPostNotification as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $dailyPostNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Daily mailer notification
        $dailyNotificationMailerValues = [];
        $dailyMailerNotification = json_decode($this->ReadPropertyString('DailyMailerNotification'), true);
        $amountDailyMailerNotification = count($dailyMailerNotification) + 1;
        if ($amountDailyMailerNotification == 1) {
            $amountDailyMailerNotification = 3;
        }
        foreach ($dailyMailerNotification as $element) {
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

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel7',
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
                    'caption' => 'Kritische Melder auch an nicht ausgewählten Tagen zurücksetzen'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                //Daily notification
                [
                    'type'    => 'Label',
                    'caption' => 'Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyNotification',
                    'rowCount' => $amountDailyNotification,
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                //Daily push notification
                [
                    'type'    => 'Label',
                    'caption' => 'Push-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyPushNotification',
                    'rowCount' => $amountDailyPushNotification,
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                //Daily post notification
                [
                    'type'    => 'Label',
                    'caption' => 'Post-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyPostNotification',
                    'rowCount' => $amountDailyPostNotification,
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
                            'caption' => 'Kachel Visualisierung',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "DailyPostNotificationConfigurationButton", "ID " . $DailyPostNotification["ID"] . " konfigurieren", $DailyPostNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::TILE_VISUALISATION_MODULE_GUID
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'caption' => 'Sound',
                            'name'    => 'LowBatterySound',
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
                            'caption' => 'Ziel ID',
                            'name'    => 'LowBatteryTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                            'caption' => 'Ziel ID',
                            'name'    => 'BatteryOKTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ]
                    ],
                    'values' => $dailyPostNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "TileVisualisation");'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'DailyPostNotificationConfigurationButton',
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
                //Daily email notification
                [
                    'type'    => 'Label',
                    'caption' => 'E-Mail',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DailyMailerNotification',
                    'rowCount' => $amountDailyMailerNotification,
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
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "DailyNotificationMailerConfigurationButton", "ID " . $DailyMailerNotification["ID"] . " konfigurieren", $DailyMailerNotification["ID"]);',
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
                            'add'     => '⚠️%1$s',
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
                            'add'     => '🟢 %1$s',
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

        //Weekly notification
        $weeklyNotificationValues = [];
        $weeklyNotification = json_decode($this->ReadPropertyString('WeeklyNotification'), true);
        $amountWeeklyNotification = count($weeklyNotification) + 1;
        if ($amountWeeklyNotification == 1) {
            $amountWeeklyNotification = 3;
        }
        foreach ($weeklyNotification as $element) {
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
        $weeklyPushNotification = json_decode($this->ReadPropertyString('WeeklyPushNotification'), true);
        $amountWeeklyPushNotification = count($weeklyPushNotification) + 1;
        if ($amountWeeklyPushNotification == 1) {
            $amountWeeklyPushNotification = 3;
        }
        foreach ($weeklyPushNotification as $element) {
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

        //Weekly post notification
        $weeklyPostNotificationValues = [];
        $weeklyPostNotification = json_decode($this->ReadPropertyString('WeeklyPostNotification'), true);
        $amountWeeklyPostNotification = count($weeklyPostNotification) + 1;
        if ($amountWeeklyPostNotification == 1) {
            $amountWeeklyPostNotification = 3;
        }
        foreach ($weeklyPostNotification as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $weeklyPostNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Weekly mailer notification
        $weeklyNotificationMailerValues = [];
        $weeklyMailerNotification = json_decode($this->ReadPropertyString('WeeklyMailerNotification'), true);
        $amountWeeklyMailerNotification = count($weeklyMailerNotification) + 1;
        if ($amountWeeklyMailerNotification == 1) {
            $amountWeeklyMailerNotification = 3;
        }
        foreach ($weeklyMailerNotification as $element) {
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

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel8',
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
                //Weekly notification
                [
                    'type'    => 'Label',
                    'caption' => 'Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyNotification',
                    'rowCount' => $amountWeeklyNotification,
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                //Weekly push notification
                [
                    'type'    => 'Label',
                    'caption' => 'Push-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyPushNotification',
                    'rowCount' => $amountWeeklyPushNotification,
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                //Weekly post notification
                [
                    'type'    => 'Label',
                    'caption' => 'Post-Nachricht',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyPostNotification',
                    'rowCount' => $amountWeeklyPostNotification,
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
                            'caption' => 'Kachel Visualisierung',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "WeeklyPostNotificationConfigurationButton", "ID " . $WeeklyPostNotification["ID"] . " konfigurieren", $WeeklyPostNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::TILE_VISUALISATION_MODULE_GUID
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
                            'add'     => '⚠️%1$s Batterie schwach',
                            'visible' => false,
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
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
                            'caption' => 'Sound',
                            'name'    => 'LowBatterySound',
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
                            'caption' => 'Ziel ID',
                            'name'    => 'LowBatteryTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
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
                            'add'     => '🟢 %1$s Batterie OK',
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
                            'caption' => 'Ziel ID',
                            'name'    => 'BatteryOKTargetID',
                            'width'   => '200px',
                            'add'     => 1,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectObject'
                            ]
                        ]
                    ],
                    'values' => $weeklyPostNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "TileVisualisation");'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'WeeklyPostNotificationConfigurationButton',
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
                //Weekly email notification
                [
                    'type'    => 'Label',
                    'caption' => 'E-Mail',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyMailerNotification',
                    'rowCount' => $amountWeeklyMailerNotification,
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
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "WeeklyNotificationMailerConfigurationButton", "ID " . $WeeklyMailerNotification["ID"] . " konfigurieren", $WeeklyMailerNotification["ID"]);',
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
                            'add'     => '⚠️%1$s',
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
                            'add'     => '🟢 %1$s',
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

        //Visualisation
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel9',
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

        //Notifications
        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => 'Benachrichtigungen'
            ];

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
                                    'onClick' => self::MODULE_PREFIX . '_ExecuteWeeklyNotification($id, false, false);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die wöchentliche Benachrichtigung wurde versendet!");'
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
                                    'onClick' => self::MODULE_PREFIX . '_ResetNotificationLists($id);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Die Listen wurden zurückgesetzt!");'
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

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => 'Schaltelemente'
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

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        $amountReferences = count($references);
        if ($amountReferences == 0) {
            $amountReferences = 3;
        }
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $location = IPS_GetLocation($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID'         => $reference,
                'Name'             => $name,
                'VariableLocation' => $location,
                'rowColor'         => $rowColor];
        }

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        $amountMessages = count($messages);
        if ($amountMessages == 0) {
            $amountMessages = 3;
        }
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $location = IPS_GetLocation($id);
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
                'VariableLocation'   => $location,
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
                    'caption' => 'Benachrichtigungen',
                    'italic'  => true,
                    'bold'    => true
                ],
                //Immediate notification
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Sofortige Benachrichtigung',
                    'popup'   => [
                        'caption' => 'Sofortige Benachrichtigung',
                        'items'   => [
                            [
                                'type'  => 'RowLayout',
                                'items' => [
                                    [
                                        'type'    => 'Label',
                                        'caption' => '⚠️ '
                                    ],
                                    [
                                        'type'    => 'Label',
                                        'caption' => 'Batterie schwach',
                                        'bold'    => true,
                                        'italic'  => true
                                    ]
                                ]
                            ],
                            [
                                'type'     => 'List',
                                'name'     => 'ImmediateNotificationListDeviceStatusLowBattery',
                                'delete'   => true,
                                'onDelete' => self::MODULE_PREFIX . '_DeleteElementFromAttribute($id, "ImmediateNotificationListDeviceStatusLowBattery", $ImmediateNotificationListDeviceStatusLowBattery["ID"]);',
                                'rowCount' => 1,
                                'sort'     => [
                                    'column'    => 'Name',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'name'    => 'ID',
                                        'caption' => 'Variable ID',
                                        'width'   => '110px',
                                        'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "ImmediateNotificationLowBatteryConfigurationButton", "ID " . $ImmediateNotificationListDeviceStatusLowBattery["ID"] . " bearbeiten", $ImmediateNotificationListDeviceStatusLowBattery["ID"]);'
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
                                ]
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'ImmediateNotificationLowBatteryConfigurationButton',
                                'caption'  => 'Bearbeiten',
                                'visible'  => false,
                                'objectID' => 0
                            ],
                            [
                                'type'    => 'Label',
                                'caption' => ' '
                            ],
                            [
                                'type'  => 'RowLayout',
                                'items' => [
                                    [
                                        'type'    => 'Label',
                                        'caption' => '🟢 '
                                    ],
                                    [
                                        'type'    => 'Label',
                                        'caption' => 'Batterie OK',
                                        'bold'    => true,
                                        'italic'  => true
                                    ]
                                ]
                            ],
                            [
                                'type'     => 'List',
                                'name'     => 'ImmediateNotificationListDeviceStatusNormal',
                                'delete'   => true,
                                'onDelete' => self::MODULE_PREFIX . '_DeleteElementFromAttribute($id, "ImmediateNotificationListDeviceStatusNormal", $ImmediateNotificationListDeviceStatusNormal["ID"]);',
                                'rowCount' => 1,
                                'sort'     => [
                                    'column'    => 'Name',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'name'    => 'ID',
                                        'caption' => 'Variable ID',
                                        'width'   => '110px',
                                        'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "ImmediateNotificationNormalConfigurationButton", "ID " . $ImmediateNotificationListDeviceStatusNormal["ID"] . " bearbeiten", $ImmediateNotificationListDeviceStatusNormal["ID"]);'
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
                                ]
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'ImmediateNotificationNormalConfigurationButton',
                                'caption'  => 'Bearbeiten',
                                'visible'  => false,
                                'objectID' => 0
                            ]
                        ]
                    ],
                    'onClick' => self::MODULE_PREFIX . '_GetImmediateNotificationStatus($id);'
                ],
                //Daily notification
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Tägliche Benachrichtigung',
                    'popup'   => [
                        'caption' => 'Tägliche Benachrichtigung',
                        'items'   => [
                            [
                                'type'  => 'RowLayout',
                                'items' => [
                                    [
                                        'type'    => 'Label',
                                        'caption' => '⚠️ '
                                    ],
                                    [
                                        'type'    => 'Label',
                                        'caption' => 'Batterie schwach',
                                        'bold'    => true,
                                        'italic'  => true
                                    ]
                                ]
                            ],
                            [
                                'type'     => 'List',
                                'name'     => 'DailyNotificationListDeviceStatusLowBattery',
                                'delete'   => true,
                                'onDelete' => self::MODULE_PREFIX . '_DeleteElementFromAttribute($id, "DailyNotificationListDeviceStatusLowBattery", $DailyNotificationListDeviceStatusLowBattery["ID"]);',
                                'rowCount' => 1,
                                'sort'     => [
                                    'column'    => 'Name',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'name'    => 'ID',
                                        'caption' => 'Variable ID',
                                        'width'   => '110px',
                                        'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "DailyNotificationLowBatteryConfigurationButton", "ID " . $DailyNotificationListDeviceStatusLowBattery["ID"] . " bearbeiten", $DailyNotificationListDeviceStatusLowBattery["ID"]);'
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
                                ]
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'DailyNotificationLowBatteryConfigurationButton',
                                'caption'  => 'Bearbeiten',
                                'visible'  => false,
                                'objectID' => 0
                            ],
                        ]
                    ],
                    'onClick' => self::MODULE_PREFIX . '_GetDailyNotificationStatus($id);'
                ],
                //Weekly notification
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Wöchentliche Benachrichtigung',
                    'popup'   => [
                        'caption' => 'Wöchentliche Benachrichtigung',
                        'items'   => [
                            [
                                'type'  => 'RowLayout',
                                'items' => [
                                    [
                                        'type'    => 'Label',
                                        'caption' => '⚠️ '
                                    ],
                                    [
                                        'type'    => 'Label',
                                        'caption' => 'Batterie schwach',
                                        'bold'    => true,
                                        'italic'  => true
                                    ]
                                ]
                            ],
                            [
                                'type'     => 'List',
                                'name'     => 'WeeklyNotificationListDeviceStatusLowBattery',
                                'delete'   => true,
                                'onDelete' => self::MODULE_PREFIX . '_DeleteElementFromAttribute($id, "WeeklyNotificationListDeviceStatusLowBattery", $WeeklyNotificationListDeviceStatusLowBattery["ID"]);',
                                'rowCount' => 1,
                                'sort'     => [
                                    'column'    => 'Name',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'name'    => 'ID',
                                        'caption' => 'Variable ID',
                                        'width'   => '110px',
                                        'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "WeeklyNotificationLowBatteryConfigurationButton", "ID " . $WeeklyNotificationListDeviceStatusLowBattery["ID"] . " bearbeiten", $WeeklyNotificationListDeviceStatusLowBattery["ID"]);'
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
                                ]
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'WeeklyNotificationLowBatteryConfigurationButton',
                                'caption'  => 'Bearbeiten',
                                'visible'  => false,
                                'objectID' => 0
                            ],
                        ]
                    ],
                    'onClick' => self::MODULE_PREFIX . '_GetWeeklyNotificationStatus($id);'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                //Registered references
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Referenzen',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'rowCount' => $amountReferences,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " bearbeiten", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px'
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                //Registered messages
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Nachrichten',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'rowCount' => $amountMessages,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " bearbeiten", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px'
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
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
                    'caption'  => 'Bearbeiten',
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