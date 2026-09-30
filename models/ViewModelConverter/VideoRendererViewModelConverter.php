<?php
namespace Rehike\Model\ViewModelConverter;

use Rehike\Util\ParsingUtils;
use Rehike\i18n\i18n;
use Rehike\Model\Common\MCollaborator;

/**
 * Converts a video renderer view model to the classic format that Rehike expects.
 * 
 * @author aubymori <aubyomori@gmail.com>
 * @author The Rehike Maintainers
 */
class VideoRendererViewModelConverter extends BasicVMC
{
    private bool $fixWatchRecommendationMetadata = false;

    public function getFixWatchRecommendationMetadata(): bool
    {
        return $this->fixWatchRecommendationMetadata;
    }

    public function setFixWatchRecommendationMetadata(bool $newValue): void
    {
        $this->fixWatchRecommendationMetadata = $newValue;
    }

    public function bake(?LockupViewModelConverter $parent): object
    {
        $result = (object)[];

        $metadata = $this->viewModel->metadata->lockupMetadataViewModel;
        $result->title = (object)[
            "simpleText" => $metadata->title->content
        ];
        $result->videoId = $this->viewModel->contentId;

        $thumbnail = $this->viewModel->contentImage->thumbnailViewModel;
        $result->thumbnail = (object)[
            "thumbnails" => $thumbnail->image->sources
        ];
        
        $i18n = i18n::getNamespace("global");
        
        $result->thumbnailOverlays = [];
        $result->thumbnailOverlays[] = (object)[
            "thumbnailOverlayToggleButtonRenderer" => (object)[
                "untoggledIcon" => (object)[
                    "iconType" => "WATCH_LATER",
                ],
                "toggledIcon" => (object)[
                    "iconType" => "WATCH_LATER",
                ],
                "untoggledTooltip" => $i18n->get("addToWatchLater"),
                "toggledTooltip" => $i18n->get("addedToWatchLater"),
                "isToggled" => false,
            ],
        ];

        if (is_array(@$thumbnail->overlays))
        {
            foreach ($thumbnail->overlays as $overlay)
            foreach ($overlay as $name => $content)
            {
                switch ($name)
                {
                    case "animatedThumbnailOverlayViewModel":
                        $result->richThumbnail = (object)[
                            "movingThumbnailRenderer" => (object)[
                                "enableHoveredLogging" => true,
                                "enableOverlay" => true,
                                "movingThumbnailDetails" => (object)[
                                    "logAsMovingThumbnail" => true,
                                    "thumbnails" => $content->thumbnail->sources
                                ]
                            ]
                        ];
                        break;
                    case "thumbnailOverlayBadgeViewModel":
                        foreach ($content->thumbnailBadges as $tbadge)
                        {
                            $tcontent = $tbadge->thumbnailBadgeViewModel;
                            switch ($tcontent->badgeStyle)
                            {
                                case "THUMBNAIL_OVERLAY_BADGE_STYLE_LIVE":
                                    $strings = i18n::getNamespace("browse");
                                    $text = $tcontent->text ?? '';
                                    $text = ($text == $strings->get("liveBadgeOriginal"))
                                        ? $strings->get("liveBadge")
                                        : $text;
                                    if (!isset($result->badges))
                                        $result->badges = [];
                                    $result->badges[] = (object)[
                                        "metadataBadgeRenderer" => (object)[
                                            "label" => $text,
                                            "style" => "BADGE_STYLE_TYPE_LIVE_NOW"
                                        ]
                                    ];
                                    break;
                                default:
                                    if (isset($tcontent->text))
                                    {
                                        $result->thumbnailOverlays[] = (object)[
                                            "thumbnailOverlayTimeStatusRenderer" => (object)[
                                                "style" => "DEFAULT",
                                                "text" => (object)[
                                                    "accessibility" => (object)[
                                                        "accessibilityData" => (object)[
                                                            "label" => @$tcontent->rendererContext->accessibilityContext->label ?? ""
                                                        ]
                                                    ],
                                                    "simpleText" => $tcontent->text
                                                ]
                                            ]
                                        ];
                                    }
                                    if (isset($tcontent->animatedText))
                                    {
                                        $result->thumbnailOverlays[] = (object)[
                                            "thumbnailOverlayNowPlayingRenderer" => (object)[
                                                "text" => (object)[
                                                    "runs" => [
                                                        (object)[
                                                            "text" => $tcontent->animatedText
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ];
                                    }
                                    break;
                            }
                            
                        }
                        break;
                    case "thumbnailBottomOverlayViewModel":
                    {
                        if (isset($content->progressBar))
                        {
                            $result->thumbnailOverlays[] = (object)[
                                "thumbnailOverlayResumePlaybackRenderer" => (object)[
                                    "percentDurationWatched" => $content->progressBar->thumbnailOverlayProgressBarViewModel->startPercent
                                ]
                            ];
                        }
                        
                        $badge = @$content->badges[0]->thumbnailBadgeViewModel ?? null;
                        if (!is_null($badge))
                        {
                            if (isset($badge->text))
                            {
                                $result->thumbnailOverlays[] = (object)[
                                    "thumbnailOverlayTimeStatusRenderer" => (object)[
                                        "style" => "DEFAULT",
                                        "text" => (object)[
                                            "accessibility" => (object)[
                                                "accessibilityData" => (object)[
                                                    "label" => @$badge->rendererContext->accessibilityContext->label ?? ""
                                                ]
                                            ],
                                            "simpleText" => $badge->text
                                        ]
                                    ]
                                ];
                            }
                            if (isset($badge->animatedText))
                            {
                                $result->thumbnailOverlays[] = (object)[
                                    "thumbnailOverlayNowPlayingRenderer" => (object)[
                                        "text" => (object)[
                                            "runs" => [
                                                (object)[
                                                    "text" => $badge->animatedText
                                                ]
                                            ]
                                        ]
                                    ]
                                ];
                            }
                        }
                        break;
                    }
                }
            }
        }
    
        $metadataRows = $metadata->metadata->contentMetadataViewModel->metadataRows;
        foreach ($metadataRows as $rowId => $contents)
        {
            if (isset($contents->metadataParts))
            foreach ($contents->metadataParts as $partId => $part)
            {
                $hasBadges = isset($metadataRows[array_key_last($metadataRows)]->badges);
                $rowCount = count($metadataRows);
                // Author text.
                if ($rowId == 0 &&
                ((!$hasBadges && $rowCount == 2) || ($hasBadges && $rowCount == 3)))
                {
                    $bylineText = ParsingUtils::indexedRunsToRuns($part->text);
                    
                    // Handle the multiple authors bullshit by just grabbing the first one.
                    $run = &$bylineText->runs[0];
                    if (isset($run->navigationEndpoint->showDialogCommand))
                    {
                        $channel = new MCollaborator($run->navigationEndpoint->showDialogCommand->panelLoadingStrategy->inlineContent->dialogViewModel->customContent
                            ->listViewModel->listItems[0]);

                        $run->text = $channel->name;
                        $run->navigationEndpoint = $channel->navigationEndpoint;
                        $badgeIcon = @$channel->rawData->title->attachmentRuns[0]->element->type->imageType->image->sources[0]->clientResource->imageName ?? null;
                    }
                    
                    $result->shortBylineText = $bylineText;
                    $result->longBylineText = $bylineText;

                    // Just fucking kill me now.
                    $badgeIcon = @$part->text->attachmentRuns[0]->element->type->imageType->image->sources[0]->clientResource->imageName ?? null;
                    $badgeIcon = match ($badgeIcon)
                    {
                        "CHECK_CIRCLE_FILLED" => "CHECK_CIRCLE_THICK",
                        "AUDIO_BADGE" => "MUSIC_NOTE",
                        default => null
                    };
                    if (!is_null($badgeIcon))
                    {
                        $result->ownerBadges = [
                            (object)[
                                "metadataBadgeRenderer" => (object)[
                                    "icon" => (object)[
                                        "iconType" => $badgeIcon
                                    ],
                                    "style" => "BADGE_STYLE_TYPE_VERIFIED"
                                ]
                            ]
                        ];
                    }
                }
                // Below byline (view count and date)
                else if (($rowCount == 1)
                || (!$hasBadges && $rowCount == 2 && $rowId == 1)
                || ($hasBadges && $rowCount == 3 && $rowId == 1))
                {
                    $memberName = match ($partId)
                    {
                        0 => "viewCountText",
                        1 => "publishedTimeText",
                        default => null
                    };
                    if (!is_null($memberName))
                    {
                        $result->{$memberName} = ParsingUtils::indexedRunsToRuns($part->text);
                    }
                }
            }
            
            // 2026-09: YouTube changed the appearance of dates on watch
            // recommendations. This might expand, but it seems isolated
            // for now.
            if ($this->fixWatchRecommendationMetadata && isset($result->publishedTimeText))
            {
                $result->publishedTimeText = $this->convertDate(
                    ParsingUtils::getText($result->publishedTimeText)
                );
            }
            
            if (isset($contents->badges))
            foreach ($contents->badges as $badge)
            {
                if (!isset($result->badges))
                    $result->badges = [];

                $badgeInner = $badge->badgeViewModel;
                $result->badges[] = (object)[
                    "metadataBadgeRenderer" => (object)[
                        "label" => $badgeInner->badgeText,
                        "style" => match ($badgeInner->badgeStyle)
                        {
                            "BADGE_COMMERCE" => "BADGE_STYLE_TYPE_YPC",
                            default => "BADGE_STYLE_TYPE_SIMPLE"
                        }
                    ]
                ];
            }
        }

        $result->navigationEndpoint = $this->viewModel->rendererContext->commandContext->onTap->innertubeCommand;

        if (isset($metadata->menuButton->buttonViewModel->onTap->innertubeCommand
            ->showSheetCommand->panelLoadingStrategy->inlineContent
            ->sheetViewModel->content->listViewModel->listItems))
        {
            $menuItems = $this->convertMenuList($metadata->menuButton->buttonViewModel->onTap->innertubeCommand
                ->showSheetCommand->panelLoadingStrategy->inlineContent
                ->sheetViewModel->content->listViewModel->listItems);
            
            if (!empty($menuItems))
            {
                $result->menu = (object)[
                    "menuRenderer" => (object)[
                        "items" => $menuItems,
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * Converts a new style date, i.e. "1d ago", to an old style date ("1 day
     * ago")
     * 
     * Much of this function is copied from the German translation code from the
     * watch comments model.
     */
    private function convertDate(string $date): string
    {
        $i18nDateRules = i18n::getNamespace("short_date_rules");
        $i18nComments = i18n::getNamespace("comments");

        \Rehike\Logging\DebugLogger::print(
            "[" . __METHOD__ . "] " .
            "Converting date \"%s\"...",
            $date
        );

        $number = 0;
        $newUnit = "none";

        $rulesList = [
            "secondsAgo",
            "minutesAgo",
            "hoursAgo",
            "daysAgo",
            "weeksAgo",
            "monthsAgo",
            "yearsAgo",
        ];

        foreach ($rulesList as $rule)
        {
            if (preg_match($i18nDateRules->get($rule), $date, $matches))
            {
                $newUnit = $rule;
                $number = $matches[1];
                break;
            }
        }

        if ("none" == $newUnit)
        {
            \Rehike\Logging\DebugLogger::print(
                "[" . __METHOD__ . "] " .
                "Failed to match any unit for date \"%s\"",
                $date
            );
            return $date;
        }

        $suffix = $number == 1 ? "Singular" : "Plural";

        $unit = match($newUnit)
        {
            "secondsAgo" => "secondsAgo" . $suffix,
            "minutesAgo" => "minutesAgo" . $suffix,
            "hoursAgo" => "hoursAgo" . $suffix,
            "daysAgo" => "daysAgo" . $suffix,
            "weeksAgo" => "weeksAgo" . $suffix,
            "monthsAgo" => "monthsAgo" . $suffix,
            "yearsAgo" => "yearsAgo" . $suffix,
            
            // why not??
            default => "yearsAgo" . $suffix
        };

        /*
         * Some languages (e.g. Polish) have different inflections for multiple ranges of
         * numbers, unlike English which just has two (singular and plural). This is a
         * quick fix that's hacked on top in order to correct this flaw.
         *
         * Use in a CoffeeTranslation language file like the following:
         *    - secondsAgoSingular   == 1
         *    - secondsAgoPlural     == standard plural match
         *    - secondsAgoPluralEqu2 == case specifically for 2
         *    - secondsAgoPluralLastDigitEqu9 == case specifically where the last digit equals 9
         *
         * Note that this may have to be handled for all numbers in a range, so this means
         * (theoretically):
         *    - 0-60 for seconds and minutes
         *    - 1-24 for hours
         *    - 1-14 (?) for days (I think it's a fortnight anyway...)
         *    - 1-12 for months
         *    - 1-infinity for years.
         *
         * But I think that the ability to check only the last digit should suffice for most
         * needs.
         */
        $templates = $i18nComments->getAllTemplates();
        $tnum = trim($number);
        if (($newName = $unit . "Equ" . $tnum) && isset($templates->{$newName}))
        {
            $unit = $newName;
        }
        else if (($newName = $unit . "LastDigitEqu" . substr($tnum, -1)) && isset($templates->{$newName}))
        {
            $unit = $newName;
        }

        if ($unit)
        {
            $result = $i18nComments->format($unit, $number);

            return $result;
        }

        \Rehike\Logging\DebugLogger::print(
            "[" . __METHOD__ . "] " .
            "Failed to match viable unit for date \"%s\", got pattern \"%s\"",
            $date,
            $unit
        );

        return $date;
    }
    }

        return $result;
    }
}
