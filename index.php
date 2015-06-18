<?php
ini_set("memory_limit", "1024M");
set_time_limit(500);
?>
<?php
$submit = $_POST['submit'];
$pixelation = $_POST['pixelation'];
if ($submit == 'send') {
	$picture = $_FILES['picture'];
	if ($picture['error'] == 0) {
		$file_info_mime = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($file_info_mime, $picture['tmp_name']);
		$mime_valid = mime_is_valid_image($mime);
		if ($mime_valid) {
			$uploaded_image = $picture['name'];
			$upload = move_uploaded_file($picture['tmp_name'], $uploaded_image);
			if ($upload) {
				$image_data = get_image_data($uploaded_image, $mime_valid);
				if ($image_data) {
					$box_shadows = array();
					$width = imagesx($image_data);
					$height = imagesy($image_data);
					for($i = 1 ; $i < $width ; $i+=$pixelation) {
						for($j = 1 ; $j < $height ; $j+=$pixelation) {
							$rgb = imagecolorat($image_data, $i, $j);
							$colors = imagecolorsforindex($image_data, $rgb);
							$colors['imagex'] = $i;
							$colors['imagey'] = $j;
							$box_shadows[] = $colors;
						}
					}
					print_stylezime_image($box_shadows, $pixelation);
				}
				else {
					echo 'Error processing the image';
				}
			}
			else {
				echo 'Upload not sucessfull';
			}
		}
		else {
			echo 'Not a valid image';
		}
	}
	else {
		echo 'Return no picture';
	}
}

function print_stylezime_image($box_shadows, $pixelation) {
	echo '<style type="text/css">';
	echo '#stylezime_image { position:absolute; top:10%; width: 0; height: 0; box-shadow:';
	$output = null;
	for ($i = 0 ; $i < count($box_shadows) ; $i++) {
		if ($box_shadows[$i]['alpha'] <= 0) {
			$hexadecimal = rgb_to_hex($box_shadows[$i]['red'], $box_shadows[$i]['green'], $box_shadows[$i]['blue']);
			$output .= $box_shadows[$i]['imagex'] . 'px  ' . $box_shadows[$i]['imagey'] . 'px 0px ' . $pixelation . 'px ' . $hexadecimal;
			$output .= ',';
		}
	}
	$output = rtrim($output, ',');
	echo $output;
	echo '}';
	echo '</style>';
	echo '<div id="stylezime_image"></div>';
}

/// Thank you to Cory (http://snipplr.com/view/4621/)
function rgb_to_hex($r, $g, $b) {
	$hex  = "#";
	$hex .= str_pad(dechex($r), 2, "0", STR_PAD_LEFT);
	$hex .= str_pad(dechex($g), 2, "0", STR_PAD_LEFT);
	$hex .= str_pad(dechex($b), 2, "0", STR_PAD_LEFT);
	return $hex;
}

function get_image_data($picture, $type) {
	$source = 0;
	switch ($type) {
		case 'png':
			$source = imagecreatefrompng($picture);
			break;
		case 'jpg':
			$source = imagecreatefromjpeg($picture);
			break;
		case 'gif':
			$source = imagecreatefromgif($picture);
			break;
	}
	if ($source) {
		$width = imagesx($source);
		$height = imagesy($source);


		$original_transparency = imagecolortransparent($source);
		if ($original_transparency >= 0) {
			$rgb = imagecolorsforindex($source, $original_transparency);
			$original_transparency = ($rgb['red'] << 16) | ($rgb['green'] << 8) | $rgb['blue'];
			imagecolortransparent($source, imagecolorallocate($source, 0, 0, 0));
		}
		$truecolor = imagecreatetruecolor($width, $height);
		imagealphablending($source, false);
		imagesavealpha($source, true);
		imagecopy($truecolor, $source, 0, 0, 0, 0, $width, $height);
		imagedestroy($source);
		$source = $truecolor;
		if ($original_transparency >= 0) {
			imagealphablending($source, false);
			imagesavealpha($source, true);
			for ($x = 0; $x < $width; $x++)
				for ($y = 0; $y < $height; $y++)
					if (imagecolorat($source, $x, $y) == $original_transparency)
						imagesetpixel($source, $x, $y, 127 << 24);
		}


		return $source;
	}
	else {
		return false;
	}
}

function mime_is_valid_image($mime) {
	$mime_valid_images = array(
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/gif' => 'gif'
       	);
	if (array_key_exists($mime, $mime_valid_images)) {
		return $mime_valid_images[$mime];
	}
	else {
		return false;
	}
}
?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script> 
<form method="post" enctype="multipart/form-data" action="">
	<input type="file" name="picture" />
	<input type="text" name="pixelation" value="5" />
	<input type="submit" name="submit" value="send" />
</form>
<script>
$(document).ready(
    function() {
        var pagebytes = $('html').html().length;
        var kbytes = pagebytes / 1024;
        $('body').append('<p style="position:fixed; bottom:0;">Document size: '+Math.round(kbytes)+'kb</p>');
    }
);
</script>