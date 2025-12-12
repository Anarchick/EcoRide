<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class CloudinaryService
{
    private Cloudinary $cloudinary;
    private const AVATAR_FOLDER = 'ecoride/avatars';
    
    public function __construct(string $cloudinaryUrl)
    {
        $this->cloudinary = new Cloudinary($cloudinaryUrl);
    }

    /**
     * Upload avatar image to Cloudinary
     * 
     * @param UploadedFile $file The uploaded file
     * @param Uuid $userId Unique identifier for the user (UUID)
     * @return string The Cloudinary public URL
     */
    public function uploadAvatar(UploadedFile $file, Uuid $userId): string
    {
        $result = $this->cloudinary->uploadApi()->upload(
            $file->getPathname(),
            [
                'folder' => self::AVATAR_FOLDER,
                'public_id' => 'user_' . $userId->toRfc4122(),
                'overwrite' => true,
                'transformation' => [
                    'width' => 400,
                    'height' => 400,
                    'crop' => 'fill',
                    'gravity' => 'face',
                    'quality' => 'auto:good',
                    'fetch_format' => 'auto'
                ]
            ]
        );

        return $result['secure_url'];
    }

    /**
     * Delete avatar from Cloudinary
     * 
     * @param string $userId User UUID
     * @return bool Success status
     */
    public function deleteAvatar(string $userId): bool
    {
        try {
            $publicId = self::AVATAR_FOLDER . '/user_' . $userId;
            $this->cloudinary->uploadApi()->destroy($publicId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get avatar URL with specific transformation
     * 
     * @param Uuid $userId User UUID
     * @param int $width Desired width
     * @param int $height Desired height
     * @return string The transformed image URL
     */
    public function getAvatarUrl(Uuid $userId, int $width = 200, int $height = 200): string
    {
        $publicId = self::AVATAR_FOLDER . '/user_' . $userId->toRfc4122();
        
        return $this->cloudinary->image($publicId)
            ->resize(\Cloudinary\Transformation\Resize::fill($width, $height)->gravity('face'))
            ->delivery(\Cloudinary\Transformation\Quality::auto())
            ->delivery(\Cloudinary\Transformation\Format::auto())
            ->toUrl();
    }
}
