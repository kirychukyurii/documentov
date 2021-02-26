<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov 
 * @copyright Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */
class ControllerExtensionFieldViewdoc extends FieldController
{
  const FIELD_INFO = array(
    'methods' => array(),
    'compound' => true,
    'MODULE_NAME' => 'FieldViewdoc',
    'FILE_NAME'   => 'viewdoc'
  );

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    $this->response->setOutput($this->load->view('extension/field/viewdoc', $data));
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/viewdoc');
    $this->model_extension_field_viewdoc->install();
  }

  public function uninstall()
  {
    $this->load->model('extension/field/viewdoc');
    $this->model_extension_field_viewdoc->uninstall();
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {

    $this->language->load('extension/field/viewdoc');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    return $this->language->get('text_show_history') . ": " . (!empty($params['show_history']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));
  }

  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    $this->load->model('doctype/doctype');
    if (isset($data['params']['templates'])) {
      foreach ($data['params']['templates'] as &$template) {
        $template['template'] = html_entity_decode($this->model_doctype_doctype->getNamesTemplate($template['template'], $template['doctype_uid'], $this->model_doctype_doctype->getTemplateVariables()));
        $template['doctype_name'] = $this->model_doctype_doctype->getDoctypeName($template['doctype_uid']);
      }
    }

    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;
    return $this->load->view('field/viewdoc/viewdoc_form', $data)
      . $this->load->view('field/common_admin_form', array('data' => $data));
  }

  public function setParams($params)
  {
    if (!empty($params['show_history'])) {
      $params['show_history'] = (int) $params['show_history'];
    }

    if (!empty($params['templates'])) {
      $templates = [];
      foreach ($params['templates'] as $template) {
        if (!$template['doctype_uid']) {
          continue;
        }
        $templates[] = [
          'doctype_uid' => $template['doctype_uid'],
          'template'    => $this->model_doctype_doctype->getIdsTemplate($template['template'], $template['doctype_uid'])
        ];
      }
      $params['templates'] = $templates;
    }
    return $params;
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    if (!empty($data['widget_name'])) {
      return $this->load->view('field/viewdoc/viewdoc_widget_form', $data);
    } else {
      return $this->getView($data);
    }
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {
    $data = $this->setDefaultTemplateParams($data);
    $this->load->model('document/document');
    $document_info = $this->model_document_document->getDocument($data['field_value'] ?? "");
    if ($document_info) {
      if (!empty($data['show_history'])) {
        $data['histories'] = $this->model_document_document->getDocumentHistories($data['field_value']);
        $structure_ids = array();
        $this->load->model('account/customer');
        $this->load->model('tool/utils');
        foreach ($data['histories'] as &$version) {
          if (!isset($structure_ids[$version['author_uid']])) {
            $structure_ids[$version['author_uid']] = $this->model_account_customer->getCustomerName($version['author_uid']);
          }
          $version['author_name'] =  $structure_ids[$version['author_uid']];
          $version['date_added'] = $this->model_tool_utils->getDateTime($version['date_added']);
        }
      }
      $data['view_document_uid'] = $data['field_value'];

      if (!empty($data['templates'])) {
        foreach ($data['templates'] as $template) {
          if ($template['doctype_uid'] == $document_info['doctype_uid']) {
            $data_render = array(
              'document_uid' => $data['field_value'],
              'doctype_uid' => $document_info['doctype_uid'],
              'template' => htmlspecialchars_decode($template['template']),
              'conditions' => "",
              'mode' => 'view'
            );
            $data['form'] = $this->load->controller('document/document/renderTemplate', $data_render);
          }
        }
      }
      if (!isset($data['form'])) {
        $data['form'] = $this->load->controller('document/document/getView', $data['field_value']);
      }
      return $this->load->view('field/viewdoc/viewdoc_widget_view', $data);
    } else {
      return "";
    }
    // $this->load->model('document/document');
    // $this->load->model('doctype/doctype');
    // $data_doc = array(
    //   'field_uids' => array(),
    //   'filter_names' => array()
    // );
    // $data['base_url'] = $this->url->link('document/document', 'document_uid=');
    // return $this->load->view('field/viewdoc/viewdoc_widget_view', $data);
  }

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
  }
}
