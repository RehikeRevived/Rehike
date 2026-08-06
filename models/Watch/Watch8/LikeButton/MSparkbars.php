<?php
namespace Rehike\Model\Watch\Watch8\LikeButton;

/**
 * Define the sparkbars (sentiment bars; like to dislike ratio bar).
 */
class MSparkbars
{
    public float $likePercent = 50;
    public float $dislikePercent = 50;
    
    public function __construct(int $likeCount, int $dislikeCount)
    {
        if (0 != $likeCount + $dislikeCount)
        {
            $this->likePercent = ($likeCount / ($likeCount + $dislikeCount)) * 100;
            $this->dislikePercent = 100 - $this->likePercent;
        }
    }
}