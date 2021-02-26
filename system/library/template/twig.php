<?php

namespace Template;

final class Twig
{
  private $twig;
  private $data = array();

  public function __construct()
  {
    // include and register Twig auto-loader
    include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');

    \Twig_Autoloader::register();
  }

  public function set($key, $value)
  {
    $this->data[$key] = $value;
  }

  public function render($template, $cache = false)
  {
    // specify where to look for templates
    $loader = new \Twig_Loader_Filesystem();

    if (defined('DIR_CATALOG') && is_dir(DIR_MODIFICATION . 'admin/view/template/')) {
      $loader->addPath(DIR_MODIFICATION . 'admin/view/template/');
    } elseif (is_dir(DIR_MODIFICATION . 'view/theme/')) {
      $loader->addPath(DIR_MODIFICATION . 'view/theme/');
    }

    $loader->addPath(DIR_TEMPLATE);

    // initialize Twig environment
    $config = array('autoescape' => false);
    if ($cache) {
      $db_database = defined("DB_DATABASE") ? DB_DATABASE : "TEMP";
      $config['cache'] = DIR_CACHE . $db_database . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
    }

    $this->twig = new \Twig_Environment($loader, $config);

    try {
      // load template
      $template = $this->twig->loadTemplate($template . '.twig');

      return $template->render($this->data);
    } catch (Exception $e) {
      trigger_error('Error: Could not load template ' . $template . '!');
      exit();
    }
  }
}
