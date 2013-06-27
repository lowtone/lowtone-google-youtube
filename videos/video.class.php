<?php
namespace lowtone\google\youtube\videos;
use lowtone\db\records\Record,
	lowtone\io\File,
	lowtone\net\URL,
	lowtone\net\Http,
	lowtone\types\arrays\Map;

/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\libs\lowtone\google\youtube\videos
 */
class Video extends Record {
	
	const PROPERTY_ID = "id",
		PROPERTY_PUBLISHED = "published",
		PROPERTY_UPDATED = "updated",
		PROPERTY_CATEGORY = "category",
		PROPERTY_TITLE = "title",
		PROPERTY_CONTENT = "content",
		PROPERTY_CONTENT_TYPE = "content_type",
		PROPERTY_LINK = "link",
		PROPERTY_AUTHOR = "author",
		PROPERTY_YOUTUBE_ACCESS_CONTROL = "youtube_access_control",
		PROPERTY_GDATA_COMMENTS = "gdata_comments",
		PROPERTY_YOUTUBE_HD = "youtube_hd",
		PROPERTY_MEDIA_CATEGORY = "media_category",
		PROPERTY_MEDIA_CONTENT = "media_content",
		PROPERTY_MEDIA_CREDIT = "media_credit",
		PROPERTY_MEDIA_DESCRIPTION = "media_description",
		PROPERTY_MEDIA_DESCRIPTION_TYPE = "media_description_type",
		PROPERTY_MEDIA_KEYWORDS = "media_keywords",
		PROPERTY_MEDIA_LICENSE = "media_license",
		PROPERTY_MEDIA_LICENSE_TYPE = "media_license_type",
		PROPERTY_MEDIA_LICENSE_HREF = "media_license_href",
		PROPERTY_MEDIA_PLAYER = "media_player",
		PROPERTY_MEDIA_THUMBNAIL = "media_thumbnail",
		PROPERTY_MEDIA_TITLE = "media_title",
		PROPERTY_MEDIA_TITLE_TYPE = "media_title_type",
		PROPERTY_YOUTUBE_ASPECT_RATIO = "youtube_aspect_ratio",
		PROPERTY_YOUTUBE_DURATION = "youtube_duration",
		PROPERTY_YOUTUBE_UPLOADED = "youtube_uploaded",
		PROPERTY_YOUTUBE_UPLOADER_ID = "youtube_uploader_id",
		PROPERTY_YOUTUBE_VIDEO_ID = "youtube_video_id",
		PROPERTY_GDATA_RATING = "gdata_rating",
		PROPERTY_YOUTUBE_STATISTICS = "youtube_statistics",
		PROPERTY_YOUTUBE_RATING = "youtube_rating";

	public function fetchData() {
		$url = URL::fromString("https://gdata.youtube.com/feeds/api/videos/" . $this->{self::PROPERTY_YOUTUBE_VIDEO_ID});

		$url->query(array(
				"alt" => "json",
				"v" => 2
			));

		if (NULL === ($data = json_decode(Http::get((string) $url))))
			return false;

		if (!isset($data->entry))
			return false;

		return $data->entry;
	}

	/**
	 * Fetch a thumbnail for the video.
	 * @param string|array $names One or more names that identify the required 
	 * image. The first image that was found is returned.
	 * @param bool $checkData Whether to check the YouTube data. If $checkData 
	 * is set to FALSE the method will try to fetch every requisted image until 
	 * a file is found which could slow the function down.
	 * @return File|bool Returns a File instance on success or FALSE if no 
	 * matching image is found.
	 */
	public function fetchThumbnail($names = "default", $checkData = true) {
		$video = $this;

		$url = function($name) use ($checkData, $video) {
			if (!$checkData)
				return sprintf('http://img.youtube.com/vi/%s/%s.jpg', $video->{Video::PROPERTY_YOUTUBE_VIDEO_ID}, $name);

			$thumbail = false;

			foreach ($video->{Video::PROPERTY_MEDIA_THUMBNAIL} as $t) {
				if ($t["name"] != $name)
					continue;

				$thumbnail = $t;

				break;

			}

			if (false === $thumbnail) 
				return false;

			return $thumbnail["url"];
		};

		$file = false;

		foreach ($names as $name) {
			if (false === ($u = $url($name)))
				continue;

			try {
				
				$file = File::get($u);

			} catch (\Exception $e) {}

			if ($file instanceof File)
				break;

		}

		return $file;
	}

	// Static
	
	public static function fromJson($json) {
		$properties = array(
				'id.$t' => self::PROPERTY_ID,
				'published.$t' => self::PROPERTY_PUBLISHED,
				'updated.$t' => self::PROPERTY_UPDATED,
				'category' => self::PROPERTY_CATEGORY,
				'title.$t' => self::PROPERTY_TITLE,
				'content.src' => self::PROPERTY_CONTENT,
				'content.type' => self::PROPERTY_CONTENT_TYPE,
				'link' => self::PROPERTY_LINK,
				'author' => self::PROPERTY_AUTHOR,
				'yt$accessControl' => self::PROPERTY_YOUTUBE_ACCESS_CONTROL,
				'gd$comments' => self::PROPERTY_GDATA_COMMENTS,
				'yt$hd' => self::PROPERTY_YOUTUBE_HD,
				'media$group.media$category' => self::PROPERTY_MEDIA_CATEGORY,
				'media$group.media$content' => self::PROPERTY_MEDIA_CONTENT,
				'media$group.media$credit' => self::PROPERTY_MEDIA_CREDIT,
				'media$group.media$description.$t' => self::PROPERTY_MEDIA_DESCRIPTION,
				'media$group.media$description.type' => self::PROPERTY_MEDIA_DESCRIPTION_TYPE,
				'media$group.media$keywords' => self::PROPERTY_MEDIA_KEYWORDS,
				'media$group.media$license.$t' => self::PROPERTY_MEDIA_LICENSE,
				'media$group.media$license.type' => self::PROPERTY_MEDIA_LICENSE_TYPE,
				'media$group.media$license.href' => self::PROPERTY_MEDIA_LICENSE_HREF,
				'media$group.media$player.url' => self::PROPERTY_MEDIA_PLAYER,
				'media$group.media$thumbnail' => self::PROPERTY_MEDIA_THUMBNAIL,
				'media$group.media$title.$t' => self::PROPERTY_MEDIA_TITLE,
				'media$group.media$title.type' => self::PROPERTY_MEDIA_TITLE_TYPE,
				'media$group.yt$aspectRatio.$t' => self::PROPERTY_YOUTUBE_ASPECT_RATIO,
				'media$group.yt$duration.seconds' => self::PROPERTY_YOUTUBE_DURATION,
				'media$group.yt$uploaded.$t' => self::PROPERTY_YOUTUBE_UPLOADED,
				'media$group.yt$uploaderId.$t' => self::PROPERTY_YOUTUBE_UPLOADER_ID,
				'media$group.yt$videoid.$t' => self::PROPERTY_YOUTUBE_VIDEO_ID,
				'gd$rating' => self::PROPERTY_GDATA_RATING,
				'yt$statistics' => self::PROPERTY_YOUTUBE_STATISTICS,
				'yt$rating' => self::PROPERTY_YOUTUBE_RATING,
			);

		$callbacks = array(
				'gd$comments' => function($val) {
					return isset($val['gd$feedLink']) ? $val['gd$feedLink'] : NULL;
				},
				'media$group.media$thumbnail' => function($val) {
					return array_map(function($thumbnail) {
						$thumbnail["name"] = isset($thumbnail['yt$name']) ? $thumbnail['yt$name'] : NULL;

						return $thumbnail;
					}, $val);
				}
			);

		$video = array();

		$json = json_decode(json_encode($json), true);

		Map::walk(function($val, $path) use ($properties, $callbacks, &$video) {
				$path = implode(".", (array) $path);
				
				if (!isset($properties[$path]))
					return $val;

				if (isset($callbacks[$path]) && is_callable($callback = $callbacks[$path]))
					$val = call_user_func($callback, $val);

				$video[$properties[$path]] = $val;
				
				return $val;
			}, -1, $json, true);

		return new self($video);
	}

}