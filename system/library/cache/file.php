<?php

/**
 * Категории
 * doctype_uid - изменение доктайпа + любого поля любого документа доктайпа
 * file_uid - изменения поля + значения данного поля любого документа
 * meta_doctype_uid - изменение доктайпа
 * meta_field_uid - изменение поля
 * Некоторые ключи
 * wf_field_uid - виджеты формы поля
 * wv_field_uid - виджеты просмотра поля
 * af_field_uid - админ форма поля
 */

namespace Cache;

class File
{
  private $expire,
    $cache = [], //собираемый кэш, который будет записан на hdd в методе save
    $temp = [], //временный массив считанных кэшей, чтобы не считывать их повторно
    $daemon,
    $dir_cache,
    $timeActualMax, //при удалении файла кеша со временем менее timeActual и наличии данных для его восстановления - запускаем пересоздание такого кэша
    $timeActualMin; //при удалении файла кеша со временем больше timeActual и наличии данных для его восстановления - запускаем пересоздание такого кэша
  const NO_CAT = "no_category";

  public function __construct($expire = 432000, $registry)
  {
    // $this->expire = $expire;
    // $this->daemon = $registry->get('daemon');
    // $this->timeActualMax = $registry->get('config')->get('cache_autoactualize_max') ?? 600;
    // $this->timeActualMin = $registry->get('config')->get('cache_autoactualize_min') ?? 5;
    // !file_exists(DIR_CACHE) ? mkdir(DIR_CACHE) : "";
    // $db_database = defined("DB_DATABASE") ? DB_DATABASE : "TEMP";
    // $this->dir_cache = DIR_CACHE . $db_database . DIRECTORY_SEPARATOR;
    // !file_exists($this->dir_cache) ? mkdir($this->dir_cache) : "";
    // // регистриуем save, чтобы выполнить сохранение в файлы в конце работы скрипта
    // // это необходимо для того, чтобы не выполнить одинаковые записи несколько раз; 
    // register_shutdown_function(array($this, 'save'));
  }

  public function get($key, $category = "")
  {
    return false;
    $cat = $category ? $category : $this::NO_CAT;
    if (isset($this->temp[$cat][$key])) {
      return $this->temp[$cat][$key];
    }
    $files = glob($this->dir_cache . ($category ? $category . DIRECTORY_SEPARATOR : '') . 'cache.' . $this->clearName($key) . '-*');
    if ($files) {
      $file_size = filesize($files[0]);
      if (!$file_size) {
        return false;
      }
      $handle = fopen($files[0], 'r');
      flock($handle, LOCK_SH);
      $data = @fread($handle, $file_size);
      flock($handle, LOCK_UN);
      fclose($handle);
      if (!$data) {
        return false;
      }
      $result = json_decode(trim($data), true);
      $this->temp[$cat][$key] = $result;
      return $result;
    }
    return false;
  }

  /**
   * Запись в кэш. 
   * Чтобы не создавать излишнюю нагрузку на ввод-вывод запись
   * на hdd выполняется после завершения выполнения скрипта путем вызова метода save. Поэтому в 
   * методе set только заполняется массив cache = [$key => [$value, microtime]]
   * 
   */
  public function set($key, $value, $category = "", $query = [])
  {
    return;
    if (isset($this->exceptions[$key]) || isset($this->exceptions[$category])) {
      return;
    }
    $this->cache[$key] = [
      'value'     => json_encode($value, JSON_UNESCAPED_UNICODE),
      'category'  => $category,
      'time'      => microtime(true)
    ];
    $cat = $category ? $category : $this::NO_CAT;
    $this->temp[$cat][$key] = $value;
    if ($query) {
      $this->cache[$key]['query'] = json_encode($query);
    }
  }

  /**
   * Удаление файлов кэша. В отличие от сохранения в кэш операция физ. удаления файлов осуществляется
   * сразу, чтобы исключить возможность получения старого кэша
   */
  public function delete($key, $category = "", $delete_category = true)
  {
    return
      $cat = $category ? $category : $this::NO_CAT;
    //удаляем папку из кэша по категории
    if (($category && !$key) || ($category && $delete_category)) {
      $path = $this->dir_cache . $this->clearName($category);
      if (file_exists($path)) {
        $this->delDir($path, true);
      }
      unset($this->temp[$category]);
    }
    if ($key) {
      $files = glob($this->dir_cache . 'cache.' . $this->clearName($key) . '-*');
      if ($files) {
        foreach ($files as $file) {
          echo $file;
          $this->delFile($file);
        }
      }
      unset($this->temp[$cat][$key]);
    }
    //если ключ или категория удаляется из кэша, удаляем также из $this->cache
    $cache = [];
    foreach ($this->cache as $cache_key => $cache_value) {
      if ($key !== $cache_key && $cache_value['category'] !== $category) {
        $cache[$cache_key] = $cache_value;
      }
    }
    $this->cache = $cache;
  }

  /**
   * Запись на hdd. Если есть рабочий демон, отдаем задачу ему.
   * Создаются файлы с названием cache.$key.microtime(), в который пишется $value,
   * а также query.$key.microtime() - если был передан запрос, по котором можно 
   * воссоздать кэш (автоактуализация)
   * Перед записью проверяем наличие такого же файла 
   * с более свежим временем, может быть, запись была произведена от другого процесса
   */
  public function save($cache = [])
  {
    return;
    if (!$cache && !$this->cache) {
      //делать нечего
      return;
    }
    $result = [];
    if (empty($cache)) {
      //запуск НЕ через демон; проверим наличие демона и, если работает, отдадим работу ему
      // if ($this->daemon->getStatus()) {
      //   //отдаем работу демону, завершаем работу
      //   $this->daemon->addTask('daemon/queue/runFileCacheRefreshTask', $this->cache, 3);
      //   return;
      // }
    } else {
      //получили $cache - работаем через демон
      $this->cache = $cache;
    }
    //удаляем устаревшие файлы
    $files = glob($this->dir_cache . 'cache.*');
    if ($files) {
      $this->garbage($files);
    }
    //сохраняем кэш в файлы
    foreach ($this->cache as $key => $info) {
      $filename = 'cache.' . $this->clearName($key);
      $path = "";
      if ($info['category']) {
        $path = $this->clearName($info['category']) . DIRECTORY_SEPARATOR;
        $filename = $path . $filename;
        if (!file_exists($this->dir_cache . $path)) {
          @mkdir($this->dir_cache . $path);
        }
      }
      // проверяем наличие более свежего кэша    
      $files = glob($this->dir_cache . $filename . '*');
      if ($files) {
        //файл(ы) с таким ключом есть
        if (!$this->garbage($files, $info['time'])) {
          continue;
        }
      }
      $this->saveFile($filename, $info['time'], $info['value']);
      $result[] = $filename;
      if (!empty($info['query'])) {
        $filename = $path . 'query.' . $this->clearName($key);
        $this->saveFile($filename, $info['time'], $info['query']);
      }
    }
    $this->cache = [];
    return $result;
  }

  /**
   * Полная очистка кэша
   */
  public function clear()
  {
    return
      $this->cache = [];
    $this->temp = [];
    $this->delDir($this->dir_cache, false);
  }

  /**
   * Метод удаления устаревших файлов (со временем старше $this->expire)
   * Возвращает false, если при очистке нашлись файлы старше временной метки - используется
   * для проверки на более свежие файлы перед сохранение кэша в файл
   */
  private function garbage($files, $time_expire = "")
  {
    if (!$time_expire) {
      $time_expire = microtime(true) - $this->expire;
    }
    if ($files) {
      $result = true;
      foreach ($files as $file) {
        $time_file = substr(strrchr($file, '-'), 1);
        if ($time_file <= $time_expire) {
          if (file_exists($file)) {
            unlink($file);
          }
          $qfile = str_replace('/cache.', '/query.', $file);
          if (file_exists($qfile)) {
            @unlink($qfile);
          }
        } else {
          $result = false;
        }
      }
    }
    //проверяем наличие кэша для неактуальных баз
    $files = glob(DIR_CACHE . '*');
    if (count($files) > 1) {
      foreach ($files as $file) {
        if ($file . DIRECTORY_SEPARATOR != $this->dir_cache) {
          $this->delDir($file);
        }
      }
    }
    return $result;
  }

  /**
   * Удаляем директории, если $delete_dir = false сама директория не удаляется (вложенные удаляются) 
   */
  private function delDir($path, $delete_dir = true)
  {
    $dir = @opendir($path);
    if ($dir === false) {
      return;
    }
    while (false !== ($file = readdir($dir))) {
      if (($file != '.') && ($file != '..')) {
        $full = $path . '/' . $file;
        if (is_dir($full)) {
          $this->delDir($full, true);
        } else {
          $this->delFile($full);
        }
      }
    }
    closedir($dir);
    if ($delete_dir) {
      @rmdir($path);
    }
  }
  private function delFile($file)
  {
    $qfile = str_replace('/cache.', '/query.', $file);
    $time_file = substr(strrchr($file, '-'), 1);
    if (
      $this->daemon->getStatus() && $time_file >= microtime(true) - $this->timeActualMax && $time_file < microtime(true) - $this->timeActualMin
      && file_exists($qfile)
    ) {
      $query = file_get_contents($qfile, true);
      // $this->daemon->addTask('daemon/queue/runFileCacheAppendTask', json_decode($query, true), 3);
    }
    if (file_exists($file)) {
      @unlink($file);
    }
    if (file_exists($qfile)) {
      @unlink($qfile);
    }
  }

  private function clearName($name)
  {
    return preg_replace('/[^A-Z0-9\._-]/i', '', $name);
  }

  private function saveFile($filename, $time, $value)
  {
    $file = $this->dir_cache . $filename . '-' . $time;
    @$handle = fopen($file, 'w');
    @flock($handle, LOCK_EX);
    @fwrite($handle, $value);
    @fflush($handle);
    @flock($handle, LOCK_UN);
    @fclose($handle);
  }
}
