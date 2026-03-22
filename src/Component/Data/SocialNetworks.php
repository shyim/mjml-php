<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Data;

final class SocialNetworks
{
    private const IMG_BASE_URL = 'https://www.mailjet.com/images/theme/v1/icons/ico-social/';

    /** @var array<string, array{src: string, 'background-color': string, 'share-url'?: string}>|null */
    private static ?array $networks = null;

    /**
     * @return array<string, array{src: string, 'background-color': string, 'share-url'?: string}>
     */
    public static function getAll(): array
    {
        if (self::$networks !== null) {
            return self::$networks;
        }

        $base = [
            'facebook' => [
                'share-url' => 'https://www.facebook.com/sharer/sharer.php?u=[[URL]]',
                'background-color' => '#3b5998',
                'src' => self::IMG_BASE_URL . 'facebook.png',
            ],
            'twitter' => [
                'share-url' => 'https://twitter.com/intent/tweet?url=[[URL]]',
                'background-color' => '#55acee',
                'src' => self::IMG_BASE_URL . 'twitter.png',
            ],
            'x' => [
                'share-url' => 'https://twitter.com/intent/tweet?url=[[URL]]',
                'background-color' => '#000000',
                'src' => self::IMG_BASE_URL . 'twitter-x.png',
            ],
            'google' => [
                'share-url' => 'https://plus.google.com/share?url=[[URL]]',
                'background-color' => '#dc4e41',
                'src' => self::IMG_BASE_URL . 'google-plus.png',
            ],
            'pinterest' => [
                'share-url' => 'https://pinterest.com/pin/create/button/?url=[[URL]]&media=&description=',
                'background-color' => '#bd081c',
                'src' => self::IMG_BASE_URL . 'pinterest.png',
            ],
            'linkedin' => [
                'share-url' => 'https://www.linkedin.com/shareArticle?mini=true&url=[[URL]]&title=&summary=&source=',
                'background-color' => '#0077b5',
                'src' => self::IMG_BASE_URL . 'linkedin.png',
            ],
            'instagram' => [
                'background-color' => '#3f729b',
                'src' => self::IMG_BASE_URL . 'instagram.png',
            ],
            'web' => [
                'background-color' => '#4BADE9',
                'src' => self::IMG_BASE_URL . 'web.png',
            ],
            'snapchat' => [
                'background-color' => '#FFFA54',
                'src' => self::IMG_BASE_URL . 'snapchat.png',
            ],
            'youtube' => [
                'background-color' => '#EB3323',
                'src' => self::IMG_BASE_URL . 'youtube.png',
            ],
            'tumblr' => [
                'share-url' => 'https://www.tumblr.com/widgets/share/tool?canonicalUrl=[[URL]]',
                'background-color' => '#344356',
                'src' => self::IMG_BASE_URL . 'tumblr.png',
            ],
            'github' => [
                'background-color' => '#000000',
                'src' => self::IMG_BASE_URL . 'github.png',
            ],
            'xing' => [
                'share-url' => 'https://www.xing.com/app/user?op=share&url=[[URL]]',
                'background-color' => '#296366',
                'src' => self::IMG_BASE_URL . 'xing.png',
            ],
            'vimeo' => [
                'background-color' => '#53B4E7',
                'src' => self::IMG_BASE_URL . 'vimeo.png',
            ],
            'medium' => [
                'background-color' => '#000000',
                'src' => self::IMG_BASE_URL . 'medium.png',
            ],
            'soundcloud' => [
                'background-color' => '#EF7F31',
                'src' => self::IMG_BASE_URL . 'soundcloud.png',
            ],
            'dribbble' => [
                'background-color' => '#D95988',
                'src' => self::IMG_BASE_URL . 'dribbble.png',
            ],
        ];

        // Generate -noshare variants
        foreach ($base as $key => $val) {
            $base[$key . '-noshare'] = array_merge($val, ['share-url' => '[[URL]]']);
        }

        self::$networks = $base;

        return self::$networks;
    }

    /**
     * Get a specific social network's data.
     *
     * @return array{src: string, 'background-color': string, 'share-url'?: string}|null
     */
    public static function get(string $name): ?array
    {
        return self::getAll()[$name] ?? null;
    }
}
