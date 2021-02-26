<?php

class ModelToolImage extends Model
{

  public function resize($filename, $width, $height, $dir_image = '')
  {
    if (!$dir_image) {
      $dir_image = DIR_IMAGE;
    }
    if (!is_file($dir_image . $filename) || substr(str_replace('\\', '/', realpath($dir_image . $filename)), 0, strlen($dir_image)) != str_replace('\\', '/', $dir_image)) {
      return;
    }

    $extension = pathinfo($filename, PATHINFO_EXTENSION);

    $image_old = $filename;
    list($width_orig, $height_orig, $image_type) = getimagesize($dir_image . $image_old);
    if (!$width) { //если не передана ширина - используем оригинальную
      $width = 0;
    }
    if (!$height) { //если не передана высота - используем оригинальную
      $height = 0;
    }
    $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int) $width . 'x' . (int) $height . '.' . $extension;
    if (!is_file($dir_image . $image_new) || (filemtime($dir_image . $image_old) > filemtime($dir_image . $image_new))) {

      if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) {
        return $dir_image . $image_old;
      }

      $path = '';

      $directories = explode('/', dirname($image_new));

      foreach ($directories as $directory) {
        $path = $path . '/' . $directory;

        if (!is_dir($dir_image . $path)) {
          @mkdir($dir_image . $path, 0777);
        }
      }

      if ($width_orig != $width || $height_orig != $height) {
        $image = new Image($dir_image . $image_old);
        $image->resize($width, $height);
        $image->save($dir_image . $image_new);
      } else {
        copy($dir_image . $image_old, $dir_image . $image_new);
      }
    }

    $image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +

    if ($this->request->server['HTTPS']) {
      return $this->config->get('config_ssl') . URL_IMAGE . $image_new;
    } else {
      return $this->config->get('config_url') . URL_IMAGE . $image_new;
    }
  }
}
