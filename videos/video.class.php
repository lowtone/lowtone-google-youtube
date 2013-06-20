<?php
namespace lowtone\google\youtube\videos;
use lowtone\db\records\Record,
	lowtone\net\URL,
	lowtone\net\Http;

/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\libs\lowtone\google\youtube\videos
 */
class Video extends Record {
	
	const PROPERTY_VIDEO_ID = "video_id";

	public function fetchData() {
		$url = URL::fromString("https://gdata.youtube.com/feeds/api/videos/" . $this->{self::PROPERTY_VIDEO_ID});

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

}