<?php
    /*
     *     based on code from:
     *
     * PHP function to resize an image maintaining aspect ratio
     * http://salman-w.blogspot.com/2008/10/resize-images-using-phpgd-library.html
     *
     * Creates a resized (e.g. thumbnail, small, medium, large)
     * version of an image file and saves it as another file
     */


    /**
    *
    *   TODO use pdftoimage.com to convert pdfs
    *
    *   TODO use shell with Imagemagick to convert Flyer thumb:
    *       convert Flyer_Leistungen.png -resize 350 Flyer_Leistungen_thumb.png
    *
    */


    define('CANVAS_IMAGE_MAX_WIDTH', 148);
    define('CANVAS_IMAGE_MAX_HEIGHT', 148);

    define('THUMBNAIL_IMAGE_WIDTH', 240);

    function generate_thumbnail_with_canvas($source_image_path, $thumbnail_image_path) {

        list($source_image_width, $source_image_height, $source_image_type) = getimagesize($source_image_path);

        $source_gd_image = get_image_from_path_and_type($source_image_path, $source_image_type);
        if ($source_gd_image === false) {
            return false;
        }
        $source_aspect_ratio = $source_image_width / $source_image_height;
        $thumbnail_aspect_ratio = CANVAS_IMAGE_MAX_WIDTH / CANVAS_IMAGE_MAX_HEIGHT;
        if ($source_image_width <= CANVAS_IMAGE_MAX_WIDTH && $source_image_height <= CANVAS_IMAGE_MAX_HEIGHT) {
            $thumbnail_image_width = $source_image_width;
            $thumbnail_image_height = $source_image_height;
        } elseif ($thumbnail_aspect_ratio > $source_aspect_ratio) {
            $thumbnail_image_width = (int) (CANVAS_IMAGE_MAX_HEIGHT * $source_aspect_ratio);
            $thumbnail_image_height = CANVAS_IMAGE_MAX_HEIGHT;
        } else {
            $thumbnail_image_width = CANVAS_IMAGE_MAX_WIDTH;
            $thumbnail_image_height = (int) (CANVAS_IMAGE_MAX_WIDTH / $source_aspect_ratio);
        }
        $thumbnail_gd_image = imagecreatetruecolor($thumbnail_image_width, $thumbnail_image_height);
        imagecopyresampled($thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, $thumbnail_image_width, $thumbnail_image_height, $source_image_width, $source_image_height);

        //creating background image
        $background_image = imagecreatetruecolor(CANVAS_IMAGE_MAX_WIDTH, CANVAS_IMAGE_MAX_HEIGHT);
        imagefill($background_image, 0, 0, 0x333333);

        //place canvas horizontally or vertically
        $dst_x = 0;
        $dst_y = 0;
        if ($thumbnail_image_height > $thumbnail_image_width) {
            $dst_x = (CANVAS_IMAGE_MAX_WIDTH - $thumbnail_image_width) / 2;
        } else {
            $dst_y = (CANVAS_IMAGE_MAX_HEIGHT - $thumbnail_image_height) / 2;
        }
        imagecopy($background_image, $thumbnail_gd_image, $dst_x, $dst_y, 0, 0, $thumbnail_image_width, $thumbnail_image_height);

        imagejpeg($background_image, $thumbnail_image_path, 90);

        //destroy all images
        imagedestroy($source_gd_image);
        imagedestroy($thumbnail_gd_image);
        imagedestroy($background_image);

        return true;
    }

    function get_image_from_path_and_type($source_image_path, $source_image_type) {
        switch ($source_image_type) {
            case IMAGETYPE_GIF:
                return imagecreatefromgif($source_image_path);
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($source_image_path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($source_image_path);
            default:
                return false;
        }
    }

    function scan_input_folder_for_subfolders() {
        return scandir('input');
    }

    function return_all_image_paths_from_folder($folder_path) {
        echo '<br />';
        echo($folder_path);

        $image_paths = array_filter(scandir($folder_path), remove_folders_from_array);

        //array_fill creates array with $folder_path in every field so array_map can handle it
        $tmp_folder_path_array = array_fill(0,count($image_paths), $folder_path);
        return array_map(concat_image_and_folder_path, $image_paths, $tmp_folder_path_array);
    }

    function resave_with_quality_90($inputPath, $outputPath) {
        //getimagesize[2] returns type
        $source_gd_image = get_image_from_path_and_type($inputPath, getimagesize($inputPath)[2]);
        if ($source_gd_image === false) {
            return false;
        }
        imagejpeg($source_gd_image, $outputPath, 90);
    }

    function generate_normal_thumbnail($inputPath, $outputPath) {
        list($source_image_width, $source_image_height, $source_image_type) = getimagesize($inputPath);

        $source_gd_image = get_image_from_path_and_type($inputPath, $source_image_type);
        if ($source_gd_image === false) {
            return false;
        }

        $width_ratio = $source_image_width / THUMBNAIL_IMAGE_WIDTH;
        $new_image_height = $source_image_height / $width_ratio;

        $thumbnail_gd_image = imagecreatetruecolor(THUMBNAIL_IMAGE_WIDTH, $new_image_height);
        imagecopyresampled($thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, THUMBNAIL_IMAGE_WIDTH, $new_image_height, $source_image_width, $source_image_height);

        imagejpeg($thumbnail_gd_image, $outputPath, 90);

        //destroy all temp images
        imagedestroy($thumbnail_gd_image);
        imagedestroy($source_gd_image);
    }

    function transform_image($inputPath, $index) {
        $outputPath = 'output/';
        $filename = $index . '.JPG';

        resave_with_quality_90($inputPath, $outputPath . $filename);

        $outputPath .= 'thumb/';
        generate_normal_thumbnail($inputPath, $outputPath . $filename);

        $outputPath .= 'gallery/';
        generate_thumbnail_with_canvas($inputPath, $outputPath . $filename);
    }

    function remove_folders_from_array($path) {
        return (strcmp($path, '.') !== 0 && strcmp($path, '..') !== 0);
    }

    function concat_image_and_folder_path($image_path, $folder_path) {
        return $folder_path . '/' . $image_path;
    }

    function generate_gallery_html($image_index) {
        return htmlentities("<li><a href='img/default/" . $image_index . ".JPG'><img src='img/default/thumb/gallery/" . $image_index . ".JPG' class='img-polaroid' alt></a></li>") . "<br />";
    }

    function generate_normal_html($image_index) {
        return htmlentities("<li><a href='img/default/" . $image_index . ".JPG'><img src='img/default/thumb/" . $image_index . ".JPG' class='img-polaroid' alt></a></li>") . "<br />";
    }


    //give the script unlimited time to work
    set_time_limit(0);

    /** you have to set following url paramters:
    *       resizing=true
    *       startingIndex=[int]
    */
    if (isset($_GET['resize']) && isset($_GET['startingIndex'])) {

        $total_number_of_images = 0;

        $base_index = $_GET['startingIndex'];
        $subfolders = scan_input_folder_for_subfolders();

        $array_size = sizeof($subfolders);
        if($array_size > 2) {
            for($i = 2; $i < $array_size; $i++) {
                $subfolder_path = 'input/' . $subfolders[$i];
                $image_paths = return_all_image_paths_from_folder($subfolder_path);

                echo '<br />';
                echo '<br />';
                echo '<br />';
                echo  'Folder: ' . $subfolder_path;
                echo '<br />';
                echo '<br />';

                foreach ($image_paths as $image_path) {
                    $image_index = sprintf('%06d', $base_index);
                    transform_image($image_path, $image_index);
                    $gallery_html .= generate_gallery_html($image_index);
                    $normal_html .= generate_normal_html($image_index);


                    echo $image_path;
                    echo '<br />';
                    $base_index++;

                    $total_number_of_images++;
                }

                echo "Gallery HTML: <br /><br />";
                echo $gallery_html;
                echo "<br /><br />";


                echo "Normal HTML: <br /><br />";
                echo $normal_html;

                //reset html
                $gallery_html = "";
                $normal_html = "";
            }

            echo "<br /><br />";
            echo "Total number of converted images: " . $total_number_of_images;
        }
    }

?>

