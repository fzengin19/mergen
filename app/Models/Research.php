<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class Research extends Model
{
    protected $fillable = [
        'user_id', 
        'title', 
        'additional_info',
        'target_audience',
        'content_type',
        'tone',
        'hashtags',
        'trend_data',
        'competitor_data',
        'content_ideas',
        'generated_content',
        'optimized_content',
        'status'
    ];

    protected $casts = [
        'hashtags' => AsArrayObject::class,
        'trend_data' => AsArrayObject::class,
        'competitor_data' => AsArrayObject::class,
        'content_ideas' => AsArrayObject::class,
        'generated_content' => AsArrayObject::class,
        'optimized_content' => AsArrayObject::class,
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Content type options for Instagram
     */
    public static function getContentTypes(): array
    {
        return [
            'post' => 'Standard Post',
            'reel' => 'Reel Video',
            'story' => 'Story',
            'carousel' => 'Carousel Post',
        ];
    }

    /**
     * Tone options for content
     */
    public static function getToneOptions(): array
    {
        return [
            'professional' => 'Professional',
            'casual' => 'Casual',
            'humorous' => 'Humorous',
            'inspirational' => 'Inspirational',
            'educational' => 'Educational',
            'promotional' => 'Promotional',
        ];
    }

    /**
     * Status options for workflow
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'trend_research' => 'Trend Research',
            'competitor_analysis' => 'Competitor Analysis',
            'content_ideation' => 'Content Ideation',
            'content_generation' => 'Content Generation',
            'optimization' => 'Optimization',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];
    }
}
