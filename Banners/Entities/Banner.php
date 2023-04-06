<?php

namespace Modules\Banners\Entities;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Illuminate\Database\Eloquent\Model;
use BenSampo\Enum\Traits\CastsEnums;
use Modules\Banners\Enums\BannersBannerPlacement;
use Modules\Banners\Enums\BannersBannerType;

class Banner extends Model implements HasMedia
{

    use InteractsWithMedia, CastsEnums;

    protected $table = 'banners_banner';

    protected $fillable = [
        'id',
        'title',
        'published',
        'placement',
        'page',
        'type',
        'language_id',
        'link',
        'html',
        'imglink',
        'checklist',
        'visible_owner',
        'visible_user',
    ];

    protected $enumCasts = [
        'placement' => BannersBannerPlacement::class,
        'type' => BannersBannerType::class,
    ];

    protected $casts = [
        'published' => 'boolean',
        'visible_owner' => 'boolean',
        'visible_user' => 'boolean',
        'checklist' => 'json',
    ];

    protected $appends = [
        'picture',
    ];

    protected $hidden = [
        'media',
    ];

    /**
     * Get the user's avatar
     *
     * @return string
     */
    public function getPictureAttribute() {
        $media = $this->getFirstMedia('banners_banner_picture');

        if ($media) {
            return [
                'full' => $media->getFullUrl(),
                'thumb' => $media->getFullUrl('thumb'),
            ];
        } else {
            return [
                'full' => null,
                'thumb' => null,
            ];
        }
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banners_banner_picture')->singleFile();
    }

    public function language() {
        return $this->belongsTo('App\Language');
    }

}
