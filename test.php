<?php 
$hauptbildschirm ="<?php

$hauptbildschirm = [
    'Control' => 'Screen',
    'Variant' => 'autoLayout_Sidebar_ver1.0',
    'Children' => [
        'Cash_sound' => [
            'Control' => 'Audio',
            'Properties' => [
                'Media' => 'cash-register-fake-88639',
                'Start' => 'varSound === true',
                'Y' => -65,
            ],
        ],
        'Navigation_Hauptbildschirm' => [
            'Control' => 'GroupContainer',
            'Variant' => 'manualLayoutContainer',
            'Properties' => [
                'Fill' => 'rgba(255, 255, 255, 1)',
                'Height' => 'HeaderContainer_Hauptbildschirm.Height',
                'Width' => 'HeaderContainer_Hauptbildschirm.Height',
                'X' => 'Hauptbildschirm.Width - 100',
                'Y' => 'Hauptbildschirm.Height - 100',
            ],
            'Children' => [
                'Nav_Adminbereich_Hauptbildschirm' => [
                    'Control' => 'Classic/Icon',
                    'Variant' => 'Settings',
                    'Properties' => [
                        'OnSelect' => 'Navigate(Passwort, ScreenTransition.Cover)',
                        'Color' => 'rgba(168, 0, 0, 1)',
                        'Height' => 'HeaderContainer_Hauptbildschirm.Height',
                        'Icon' => 'Icon.Settings',
                        'Width' => 'HeaderContainer_Hauptbildschirm.Height',
                    ],
                ],
            ],
        ],
        'ScreenContainer_Hauptbildschirm' => [
            'Control' => 'GroupContainer',
            'Variant' => 'verticalAutoLayoutContainer',
            'Properties' => [
                'Fill' => 'rgba(245, 245, 245, 1)',
                'Height' => 'Parent.Height',
                'LayoutAlignItems' => 'LayoutAlignItems.Stretch',
                'LayoutDirection' => 'LayoutDirection.Vertical',
                'LayoutGap' => 16,
                'LayoutMode' => 'LayoutMode.Auto',
                'PaddingBottom' => 16,
                'PaddingLeft' => 16,
                'PaddingRight' => 16,
                'PaddingTop' => 16,
                'Width' => 'Parent.Width',
            ],
            'Children' => [
                'HeaderContainer_Hauptbildschirm' => [
                    'Control' => 'GroupContainer',
                    'Variant' => 'horizontalAutoLayoutContainer',
                    'Properties' => [
                        'Fill' => 'rgba(168, 0, 0, 1)',
                        'FillPortions' => 0,
                        'Height' => 64,
                        'LayoutMode' => 'LayoutMode.Auto',
                        'RadiusBottomLeft' => 10,
                        'RadiusBottomRight' => 10,
                        'RadiusTopLeft' => 10,
                        'RadiusTopRight' => 10,
                    ],
                    'Children' => [
                        'Titel_Hauptbildschirm' => [
                            'Control' => 'Label',
                            'Properties' => [
                                'Text' => 'Kantine FF 1110',
                                'Align' => 'Align.Center',
                                'AlignInContainer' => 'AlignInContainer.Center',
                                'AutoHeight' => true,
                                'Color' => 'rgba(255, 255, 255, 1)',
                                'Fill' => 'rgba(168, 0, 0, 1)',
                                'FillPortions' => 1,
                                'FontWeight' => 'FontWeight.Bold',
                                'Height' => 64,
                                'Size' => 'min(HeaderContainer_Hauptbildschirm.Width * 0.050, 30)',
                                'Width' => 'Parent.Width / 1.08',
                                'X' => 2,
                                'Y' => 6,
                            ],
                        ],
                    ],
                ],
                'BottomContainer_Hauptbildschirm' => [
                    'Control' => 'GroupContainer',
                    'Variant' => 'horizontalAutoLayoutContainer',
                    'Properties' => [
                        'Fill' => 'rgba(245, 245, 245, 1)',
                        'LayoutAlignItems' => 'LayoutAlignItems.Stretch',
                        'LayoutGap' => 16,
                        'LayoutMode' => 'LayoutMode.Auto',
                        'LayoutWrap' => true,
                        'PaddingBottom' => 2,
                        'PaddingLeft' => 2,
                        'PaddingRight' => 2,
                        'PaddingTop' => 2,
                    ],
                    'Children' => [
                        'MainContainer_Hauptbildschirm' => [
                            'Control' => 'GroupContainer',
                            'Variant' => 'verticalAutoLayoutContainer',
                            'Properties' => [
                                'Fill' => 'rgba(255, 255, 255, 1)',
                                'FillPortions' => 7,
                                'LayoutDirection' => 'LayoutDirection.Vertical',
                                'LayoutMode' => 'LayoutMode.Auto',
                            ],
                            'Children' => [
                                'Gallery_Hauptbildschirm' => [
                                    'Control' => 'Gallery',
                                    'Variant' => 'BrowseLayout_Vertical_TwoTextOneImageVariant_ver5.0',
                                    'Properties' => [
                                        'OnSelect' => "if (isBlank(toggleWert)) { toggleWert = 3; } navigate('Persönlicher_Bereich', 'Cover', ['ID_1' => Gallery_Hauptbildschirm.Selected.ID]); setKategorie(1);",
                                        'Items' => "sortByColumns(People, 'Sortkey', 'ascending', 'Nachname', 'ascending')",
                                        'WrapCount' => "max(2, if (Gallery_Hauptbildschirm.Width > 0, roundDown(Gallery_Hauptbildschirm.Width / 230, 0), 1))",
                                        'DelayItemLoading' => true,
                                        'Fill' => 'rgba(0, 0, 0, 0)',
                                        'FillPortions' => 0,
                                        'Height' => 'MainContainer_Hauptbildschirm.Height',
                                        'Layout' => 'Vertical',
                                        'TemplatePadding' => 0,
                                        'TemplateSize' => 90,
                                        'Width' => 12,
                                        'X' => 40,
                                        'Y' => 121,
                                    ],
                                    'Children' => [
                                        'Nachname_Hauptbildschirm' => [
                                            'Control' => 'Label',
                                            'Properties' => [
                                                'OnSelect' => 'select(Parent)',
                                                'Text' => 'ThisItem.Nachname',
                                                'BorderColor' => 'rgba(0, 0, 0, 1)',
                                                'Color' => 'Color.Black',
                                                'FontWeight' => 'FontWeight.Semibold',
                                                'Height' => 28,
                                                'PaddingLeft' => 12,
                                                'Size' => 16,
                                                'VerticalAlign' => 'VerticalAlign.Top',
                                                'Width' => 'Umrandung_Hauptbildschirm.Width',
                                                'X' => 14,
                                                'Y' => 22,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];

?>
"
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hauptbildschirm</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .screen-container {
            padding: 16px;
        }
        .group-container {
            margin: 16px 0;
            padding: 16px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        .label {
            font-size: 14px;
            margin: 4px 0;
        }
        .audio-control {
            margin: 16px 0;
        }
        .icon {
            display: inline-block;
            margin: 4px;
            cursor: pointer;
            color: #a80000;
        }
    </style>
</head>
<body>
    <div class="screen-container">
        <?php
        // Recursive function to render components
        function renderComponent($component) {
            // Determine the type of control
            $controlType = $component['Control'] ?? 'Unknown';

            switch ($controlType) {
                case 'Screen':
                    echo '<div class="screen">';
                    if (!empty($component['Children'])) {
                        foreach ($component['Children'] as $child) {
                            renderComponent($child);
                        }
                    }
                    echo '</div>';
                    break;

                case 'Audio':
                    echo '<div class="audio-control">';
                    echo '<audio src="' . htmlspecialchars($component['Properties']['Media']) . '" autoplay></audio>';
                    echo '</div>';
                    break;

                case 'GroupContainer':
                    echo '<div class="group-container" style="background-color: ' . htmlspecialchars($component['Properties']['Fill'] ?? '#fff') . ';">';
                    if (!empty($component['Children'])) {
                        foreach ($component['Children'] as $child) {
                            renderComponent($child);
                        }
                    }
                    echo '</div>';
                    break;

                case 'Label':
                    echo '<div class="label" style="color: ' . htmlspecialchars($component['Properties']['Color'] ?? '#000') . ';">';
                    echo htmlspecialchars($component['Properties']['Text'] ?? '');
                    echo '</div>';
                    break;

                case 'Classic/Icon':
                    echo '<div class="icon" style="color: ' . htmlspecialchars($component['Properties']['Color'] ?? '#000') . ';">';
                    echo htmlspecialchars($component['Properties']['Icon'] ?? 'Icon');
                    echo '</div>';
                    break;

                case 'Gallery':
                    echo '<div class="gallery">';
                    if (!empty($component['Children'])) {
                        foreach ($component['Children'] as $child) {
                            renderComponent($child);
                        }
                    }
                    echo '</div>';
                    break;

                default:
                    echo '<div class="unknown-control">Unknown control type: ' . htmlspecialchars($controlType) . '</div>';
                    break;
            }
        }

        // Render the Hauptbildschirm component
        renderComponent($hauptbildschirm);
        ?>
    </div>
</body>
</html>
