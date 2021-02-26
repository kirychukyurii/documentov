<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */

class ControllerMenuItem extends Controller
{

  private $error = array();

  public function index()
  {

    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('menu/item', '', true, true));
    $this->load->language('menu/item');
    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('menu/item');

    $this->getList();
  }

  public function add()
  {
    $this->load->language('menu/item');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('menu/item');

    if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
      $this->model_menu_item->addItem($this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $url = '';

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      $this->response->redirect($this->url->link('menu/item', $url, true));
    }

    $this->getForm();
  }

  public function edit()
  {

    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('menu/item/edit' . '&item_id=' . ($this->request->get['item_id'] ?? ""), '', true, true));
    $this->load->language('menu/item');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('menu/item');

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $this->model_menu_item->editItem($this->request->get['item_id'], $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $url = '';

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      $this->response->redirect($this->url->link('menu/item', $url, true));
    }

    $this->getForm();
  }

  public function delete()
  {
    $this->load->language('menu/item');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('menu/item');

    if (isset($this->request->post['selected'])) {
      foreach ($this->request->post['selected'] as $item_id) {
        $this->model_menu_item->deleteItem($item_id);
      }

      $this->session->data['success'] = $this->language->get('text_success');

      $url = '';

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      $this->response->redirect($this->url->link('menu/item', $url, true));
    }

    $this->getList();
  }

  public function repair()
  {
    $this->load->language('menu/item');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('menu/item');

    if ($this->validateRepair()) {
      $this->model_menu_item->repairItems();

      $this->session->data['success'] = $this->language->get('text_success');

      $url = '';

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      $this->response->redirect($this->url->link('menu/item', $url, true));
    }

    $this->getList();
  }

  protected function getList()
  {
    if (isset($this->request->get['sort'])) {
      $sort = $this->request->get['sort'];
    } else {
      $sort = 'name';
    }

    if (isset($this->request->get['order'])) {
      $order = $this->request->get['order'];
    } else {
      $order = 'ASC';
    }

    $url = '';

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . $this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }


    $data['add'] = $this->url->link('menu/item/add', $url, true);
    $data['delete'] = $this->url->link('menu/item/delete', $url, true);

    $data['categories'] = array();

    $filter_data = array(
      'sort' => $sort,
      'order' => $order,
    );

    // $item_total = $this->model_menu_item->getTotalItems();

    $results = $this->model_menu_item->getItems($filter_data);
    $this->load->model('tool/image');
    foreach ($results as $result) {
      $data['items'][] = array(
        'item_id'       => $result['menu_item_id'],
        'name'          => $result['name'],
        'status'        => $result['status'],
        'image'         => $result['image'] ? $this->model_tool_image->resize($result['image'], 50, 50) : "",
        'sort_order'    => $result['sort_order'],
        'edit'          => $this->url->link('menu/item/edit', 'item_id=' . $result['menu_item_id'] . $url, true),
        'delete'        => $this->url->link('menu/item/delete', 'item_id=' . $result['menu_item_id'] . $url, true)
      );
    }
    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];

      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }

    if (isset($this->request->post['selected'])) {
      $data['selected'] = (array) $this->request->post['selected'];
    } else {
      $data['selected'] = array();
    }

    $url = '';

    if ($order == 'ASC') {
      $url .= '&order=DESC';
    } else {
      $url .= '&order=ASC';
    }

    $data['sort_name'] = $this->url->link('menu/item', 'sort=name' . $url, true);
    $data['sort_sort_order'] = $this->url->link('menu/item', 'sort=sort_order' . $url, true);
    $data['sort_status'] = $this->url->link('menu/item', 'sort=status' . $url, true);

    $url = '';

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . $this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }

    $data['sort'] = $sort;
    $data['order'] = $order;

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');
    $this->load->language('menu/item');

    $this->response->setOutput($this->load->view('menu/item_list', $data));
  }

  protected function getForm()
  {
    $this->load->model('doctype/doctype');
    $this->load->model('tool/image');

    $data['text_form'] = !isset($this->request->get['item_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    if (isset($this->error['name'])) {
      $data['error_name'] = $this->error['name'];
    } else {
      $data['error_name'] = array();
    }

    if (isset($this->error['meta_title'])) {
      $data['error_meta_title'] = $this->error['meta_title'];
    } else {
      $data['error_meta_title'] = array();
    }

    if (isset($this->error['keyword'])) {
      $data['error_keyword'] = $this->error['keyword'];
    } else {
      $data['error_keyword'] = '';
    }

    if (isset($this->error['parent'])) {
      $data['error_parent'] = $this->error['parent'];
    } else {
      $data['error_parent'] = '';
    }

    $url = '';

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . $this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . $this->request->get['order'];
    }

    if (!isset($this->request->get['item_id'])) {
      $data['action_form'] = $this->url->link('menu/item/add', $url, true);
    } else {
      $data['action_form'] = $this->url->link('menu/item/edit', 'item_id=' . $this->request->get['item_id'] . $url, true);
    }

    $data['cancel'] = $this->url->link('menu/item', $url, true);

    if (isset($this->request->get['item_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      $item_info = $this->model_menu_item->getItem($this->request->get['item_id']);
      if (!$item_info) {
        $url = $this->url->link('error/not_found');
        $this->response->redirect($url);
        return;
      }
      if ($item_info['action'] == "folder") {
        $item_info['action_id'] = $item_info['action_value'];
        if ($item_info['action_value']) {
          $this->load->model('doctype/folder');
          $folder_info = $this->model_doctype_folder->getFolder($item_info['action_id']);
          $item_info['action_value'] = isset($folder_info['name']) ? $folder_info['name'] : "";
        } else {
          $item_info['action_value'] = "";
        }
      }
    }

    $this->load->model('localisation/language');

    $data['languages'] = $this->model_localisation_language->getLanguages();

    if (isset($this->request->post['item_description'])) {
      $data['item_description'] = $this->request->post['item_description'];
    } elseif (isset($this->request->get['item_id'])) {
      $data['item_description'] = $this->model_menu_item->getItemDescriptions($this->request->get['item_id']);
    } else {
      $data['item_description'] = array();
    }

    if (isset($this->request->post['type'])) {
      $data['type'] = $this->request->post['type'];
    } elseif (!empty($item_info)) {
      $data['type'] = $item_info['type'];
    } else {
      $data['type'] = 'text';
    }

    if (isset($this->request->post['parent_id'])) {
      $data['parent_id'] = $this->request->post['parent_id'];
      $data['parent_description'] = $this->model_menu_item->getItemDescriptions($this->request->post['parent_id']);
    } elseif (!empty($item_info)) {
      $data['parent_id'] = $item_info['parent_id'];
      $parent_item_info = $this->model_menu_item->getItem($item_info['parent_id']);
      $data['parent_description'] = $parent_item_info['description'] ?? "";
      $data['parent_image'] = !empty($parent_item_info['image']) ? $this->model_tool_image->resize($parent_item_info['image'], 25, 25) : "";
    } else {
      $data['parent_id'] = '';
    }

    if (isset($this->request->post['action'])) {
      $data['action'] = $this->request->post['action'];
    } elseif (!empty($item_info)) {
      $data['action'] = $item_info['action'];
    } else {
      $data['action'] = "";
    }

    if (isset($this->request->post['action_value'])) {
      $data['action_value'] = $this->request->post['action_value'];
    } elseif (!empty($item_info)) {
      $data['action_value'] = $item_info['action_value'];
    } else {
      $data['action_value'] = "";
    }

    if (isset($this->request->post['action_id'])) {
      $data['action_id'] = $this->request->post['action_id'];
    } elseif (!empty($item_info['action_id'])) {
      $data['action_id'] = $item_info['action_id'];
    } else {
      $data['action_id'] = "";
    }

    if (isset($this->request->post['hide_name'])) {
      $data['hide_name'] = $this->request->post['hide_name'];
    } elseif (!empty($item_info)) {
      $data['hide_name'] = $item_info['hide_name'];
    } else {
      $data['hide_name'] = '';
    }

    if (isset($this->request->post['image'])) {
      $data['image'] = $this->request->post['image'];
    } elseif (!empty($item_info)) {
      $data['image'] = $item_info['image'];
    } else {
      $data['image'] = '';
    }

    $this->load->model('tool/image');

    if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
      $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
    } elseif (!empty($item_info) && is_file(DIR_IMAGE . $item_info['image'])) {
      $data['thumb'] = $this->model_tool_image->resize($item_info['image'], 100, 100);
    } else {
      $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
    }

    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);


    if (isset($this->request->post['sort_order'])) {
      $data['sort_order'] = $this->request->post['sort_order'];
    } elseif (!empty($item_info)) {
      $data['sort_order'] = $item_info['sort_order'];
    } else {
      $data['sort_order'] = 0;
    }

    if (isset($this->request->post['status'])) {
      $data['status'] = $this->request->post['status'];
    } elseif (!empty($item_info)) {
      $data['status'] = $item_info['status'];
    } else {
      $data['status'] = 1;
    }

    if (isset($this->request->post['delegate'])) {
      $data['delegate'] = $this->request->post['delegate'];
    } elseif (!empty($item_info)) {
      $data['delegate'] = $item_info['delegate'];
    } else {
      $data['delegate'] = array();
    }

    $data['language_id'] = $this->config->get('config_language_id');
    $data['structure_uid'] = $this->config->get('structure_id');
    $data['structure_name_uid'] = $this->config->get('structure_field_name_id');
    $data_structire = array(
      'doctype_uid' => $this->config->get('structure_id'),
      'setting'     => 0,
      'access_view' => 0
    );
    $data['structure_fields'] = $this->model_doctype_doctype->getFields($data_structire);

    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $this->load->language('menu/item');

    $this->response->setOutput($this->load->view('menu/item_form', $data));
  }

  protected function validateRepair()
  {
    if (!$this->user->hasPermission('modify', 'menu/item')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    return !$this->error;
  }

  public function autocomplete()
  {

    $json = array();

    if (isset($this->request->get['filter_name'])) {
      $this->load->model('menu/item');
      $this->load->model('tool/image');
      $filter_data = array(
        'filter_name' => trim($this->request->get['filter_name']),
        'sort' => 'name',
        'order' => 'ASC',
        'start' => 0,
        'limit' => 100
      );

      $results = $this->model_menu_item->getItems($filter_data);

      foreach ($results as $result) {
        //вложенность в меню должна быть ограничена тремя уровнями (Документы > Внешние > Входящие
        //это значит, что Входящие не должны попадать в выборку, чтобы не стать родителями для четвертого уровня
        //Таким образом, в выборке должны отсутствовать пункты меню c двумя вхождениями " > " 
        if (substr_count($result['name'], "&nbsp;&nbsp;&gt;&nbsp;&nbsp;") == 2) {
          continue;
        }
        $name = strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'));
        $json[] = array(
          'item_id'   => $result['menu_item_id'],
          'name'      => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
          'image'     => $result['image'] ?  $this->model_tool_image->resize($result['image'], 25, 25) : ""

        );
      }
    }

    $sort_order = array();

    foreach ($json as $key => $value) {
      $sort_order[$key] = $value['name'];
    }

    array_multisort($sort_order, SORT_ASC, $json);

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }
}
