<?php

/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @link        https://github.com/PHPOffice/PHPWord
 * @copyright   2010-2014 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord\Shared;

use PhpOffice\PhpWord\Element\AbstractContainer;

use PhpOffice\PhpWord\Settings;

use PhpOffice\PhpWord\Style\Paragraph;

/**
 * Common Html functions
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod) For readWPNode
 */
class Html
{
  /**
   * Add HTML parts
   *
   * Note: $stylesheet parameter is removed to avoid PHPMD error for unused parameter
   *
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element Where the parts need to be added
   * @param string $html The code to parse
   * @param bool $fullHTML If it's a full HTML, no need to add 'body' tag
   */
  public static function addHtml($element, $html, $fullHTML = false)
  {
    /*
         * @todo parse $stylesheet for default styles.  Should result in an array based on id, class and element,
         * which could be applied when such an element occurs in the parseNode function.
         */

    // Preprocess: remove all line ends, decode HTML entity, and add body tag for HTML fragments
    $html = str_replace(array("\n", "\r", "<br>"), '', $html);
    $html = html_entity_decode($html);

    if ($fullHTML === false) {

      $html = '<!DOCTYPE html><html dir="ltr" lang="ru"><head><meta charset="UTF-8" /></head>'
        . '<body>' . $html . '</body></html>';
    }

    // Load DOM
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = true;
    if (!$dom->loadHTML($html)) { //was loadXml()
      echo "ошибка загрузки";
      exit;
    };
    $node = $dom->getElementsByTagName('body');


    self::parseNode($node->item(0), $element);
  }

  /**
   * parse Inline style of a node
   *
   * @param \DOMNode $node Node to check on attributes and to compile a style array
   * @param array $styles is supplied, the inline style attributes are added to the already existing style
   * @return array
   */
  protected static function parseInlineStyle($node, $styles = array())
  {
    if ($node->nodeType == XML_ELEMENT_NODE) {
      $attributes = $node->attributes; // get all the attributes(eg: id, class)
      foreach ($attributes as $attribute) {
        switch ($attribute->name) {
          case 'style':
            $styles = self::parseStyle($attribute, $styles);
            break;
        }
      }
    }

    return $styles;
  }

  /**
   * Parse a node and add a corresponding element to the parent element
   *
   * @param \DOMNode $node node to parse
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element object to add an element corresponding with the node
   * @param array $styles Array with all styles
   * @param array $data Array to transport data to a next level in the DOM tree, for example level of listitems
   */
  protected static function parseNode($node, $element, $styles = array(), $data = array())
  {
    // Populate styles array
    $styleTypes = array('font', 'paragraph', 'list', 'width');
    foreach ($styleTypes as $styleType) {
      if (!isset($styles[$styleType])) {
        $styles[$styleType] = array();
      }
    }

    // Node mapping table
    $nodes = array(
      // $method        $node   $element    $styles     $data   $argument1      $argument2
      'p'         => array('Paragraph',   $node,  $element,   $styles,    null,   null,           null),
      'h1'        => array('Heading',     null,   $element,   $styles,    null,   'Heading1',     null),
      'h2'        => array('Heading',     null,   $element,   $styles,    null,   'Heading2',     null),
      'h3'        => array('Heading',     null,   $element,   $styles,    null,   'Heading3',     null),
      'h4'        => array('Heading',     null,   $element,   $styles,    null,   'Heading4',     null),
      'h5'        => array('Heading',     null,   $element,   $styles,    null,   'Heading5',     null),
      'h6'        => array('Heading',     null,   $element,   $styles,    null,   'Heading6',     null),
      '#text'     => array('Text',        $node,  $element,   $styles,    null,    null,          null),
      'span'      => array('Span',        $node,  null,       $styles,    null,    null,          null), //to catch inline span style changes
      'strong'    => array('Property',    null,   null,       $styles,    null,   'bold',         true),
      'b'         => array('Property',    null,   null,       $styles,    null,   'bold',         true),
      'em'        => array('Property',    null,   null,       $styles,    null,   'italic',       true),
      'i'         => array('Property',    null,   null,       $styles,    null,   'italic',       true),
      'u'         => array('Property',    null,   null,       $styles,    null,   'underline',    'single'),
      'sup'       => array('Property',    null,   null,       $styles,    null,   'superScript',  true),
      'sub'       => array('Property',    null,   null,       $styles,    null,   'subScript',    true),
      'span'      => array('Span',        $node,  null,       $styles,    null,   null,           null),
      'font'      => array('Span',        $node,  null,       $styles,    null,   null,           null),
      'table'     => array('Table',       $node,  $element,   $styles,    null,   'addTable',     true),
      'tbody'     => array('Table',       $node,  $element,   $styles,    null,   'skipTbody',    true), //added to catch tbody in html.
      'tr'        => array('Table',       $node,  $element,   $styles,    null,   'addRow',       true),
      'td'        => array('Table',       $node,  $element,   $styles,    null,   'addCell',      true),
      'th'        => array('Cell',        $node,  $element,   $styles,    null,   null,           null),
      'ul'        => array('List',        null,   null,       $styles,    $data,  3,              null),
      'ol'        => array('List',        null,   null,       $styles,    $data,  7,              null),
      'li'        => array('ListItem',    $node,  $element,   $styles,    $data,  null,           null),
      'img'       => array('Image',       $node,  $element,   $styles,    null,   null,           null),
      'br'        => array('LineBreak',   null,   $element,   $styles,    null,   null,           null),
      'a'         => array('Link',        $node,  $element,   $styles,    null,   null,           null),

    );

    $newElement = null;
    $keys = array('node', 'element', 'styles', 'data', 'argument1', 'argument2');

    if (array_key_exists($node->nodeName, $nodes)) {

      // Execute method based on node mapping table and return $newElement or null
      // Arguments are passed by reference
      $arguments = array();
      $args = array();
      list($method, $args[0], $args[1], $args[2], $args[3], $args[4], $args[5]) = $nodes[$node->nodeName];
      for ($i = 0; $i <= 5; $i++) {
        if ($args[$i] !== null) {
          $arguments[$keys[$i]] = &$args[$i];
        }
      }
      $method = "parse{$method}";
      $newElement = call_user_func_array(array('PhpOffice\PhpWord\Shared\Html', $method), $arguments);

      // Retrieve back variables from arguments
      foreach ($keys as $key) {
        if (array_key_exists($key, $arguments)) {
          $$key = $arguments[$key];
        }
      }
    }

    if ($newElement === null) {
      $newElement = $element;
    }

    self::parseChildNodes($node, $newElement, $styles, $data);
  }

  /**
   * Parse child nodes
   *
   * @param \DOMNode $node
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   * @param array $styles
   * @param array $data
   */
  private static function parseChildNodes($node, $element, $styles, $data)
  {
    if ($node->nodeName != 'li') {
      $cNodes = $node->childNodes;
      if (count($cNodes) > 0) {
        foreach ($cNodes as $cNode) {


          // Added to get tables to work                    
          $htmlContainers = array(
            'tbody',
            'tr',
            'td',
          );
          if (in_array($cNode->nodeName, $htmlContainers)) {
            self::parseNode($cNode, $element, $styles, $data);
          }

          // All other containers as defined in AbstractContainer
          if ($element instanceof AbstractContainer) {
            self::parseNode($cNode, $element, $styles, $data);
          }
        }
      }
    }
  }

  /**
   * Parse paragraph node
   *
   * @param \DOMNode $node
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   * @param array $styles
   * @return \PhpOffice\PhpWord\Element\TextRun
   */
  private static function parseParagraph($node, $element, &$styles)
  {
    $styles['paragraph'] = self::parseInlineStyle($node, $styles['paragraph']);
    $newElement = $element->addTextRun($styles['paragraph']);

    return $newElement;
  }

  /**
   * Parse heading node
   *
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   * @param array $styles
   * @param string $argument1 Name of heading style
   * @return \PhpOffice\PhpWord\Element\TextRun
   *
   * @todo Think of a clever way of defining header styles, now it is only based on the assumption, that
   * Heading1 - Heading6 are already defined somewhere
   */
  private static function parseHeading($element, &$styles, $argument1)
  {
    $styles['paragraph'] = $argument1;
    $newElement = $element->addTextRun($styles['paragraph']);

    return $newElement;
  }

  /**
   * Parse text node
   *
   * @param \DOMNode $node
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   * @param array $styles
   * @return null
   */
  private static function parseText($node, $element, &$styles)
  {

    $styles['font'] = self::parseInlineStyle($node, $styles['font']);
    if (!empty($styles['paragraph']['color'])) {
      $styles['font']['color'] = $styles['paragraph']['color'];
    }

    // Commented as source of bug #257. `method_exists` doesn't seems to work properly in this case.
    // @todo Find better error checking for this one
    // if (method_exists($element, 'addText')) {
    $element->addText($node->nodeValue, $styles['font'], $styles['paragraph']);
    // }


    return null;
  }


  /**
   * Parse property node
   *
   * @param array $styles
   * @param string $argument1 Style name
   * @param string $argument2 Style value
   * @return null
   */
  private static function parseProperty(&$styles, $argument1, $argument2)
  {
    $styles['font'][$argument1] = $argument2;

    return null;
  }

  /**
   * Parse table node
   *
   * @param \DOMNode $node
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   * @param array $styles
   * @param string $argument1 Method name
   * @return \PhpOffice\PhpWord\Element\AbstractContainer $element
   *
   * @todo As soon as TableItem, RowItem and CellItem support relative width and height
   */
  private static function parseTable($node, $element, &$styles, $argument1)
  {
    switch ($argument1) {
      case 'addTable':
        $styles['paragraph'] = self::parseInlineStyle($node, $styles['paragraph']);

        foreach ($node->attributes as $attr) {
          if ($attr->name == "width") {
            $width = $attr->nodeValue;
          }
          if ($attr->name == "style") {
            $style = self::parseStyle($attr, array());
            $width = !empty($style['width']) ? $style['width'] . $style['unit'] : "";
          }
        }
        $newElement = $element->addTable('table', array('width' => 9000));
        if (!empty($width)) {
          $newElement->setWidth('100%');
        }

        break;
      case 'skipTbody':
        $newElement = $element;
        break;
      case 'addRow':
        $newElement = $element->addRow();
        break;
      case 'addCell':
        $newElement = $element->addCell(1750);
        break;
    }

    //        $attributes = $node->attributes;
    //        if ($attributes->getNamedItem('width') !== null) {
    //            $newElement->setWidth($attributes->getNamedItem('width')->value);
    //        }
    //
    //        if ($attributes->getNamedItem('height') !== null) {
    //            $newElement->setHeight($attributes->getNamedItem('height')->value);
    //        }
    //        if ($attributes->getNamedItem('width') !== null) {
    //            $newElement=$element->addCell($width=$attributes->getNamedItem('width')->value);
    //        }

    return $newElement;
  }

  /**
   * Parse list node
   *
   * @param array $styles
   * @param array $data
   * @param string $argument1 List type
   * @return null
   */
  private static function parseList(&$styles, &$data, $argument1)
  {
    if (isset($data['listdepth'])) {
      $data['listdepth']++;
    } else {
      $data['listdepth'] = 0;
    }
    $styles['list']['listType'] = $argument1;

    return null;
  }

  /**
   * Parse list item node
   *
   * @param \DOMNode $node
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   * @param array $styles
   * @param array $data
   * @return null
   *
   * @todo This function is almost the same like `parseChildNodes`. Merged?
   * @todo As soon as ListItem inherits from AbstractContainer or TextRun delete parsing part of childNodes
   */
  private static function parseListItem($node, $element, &$styles, $data)
  {
    $cNodes = $node->childNodes;
    if (count($cNodes) > 0) {
      $text = '';
      foreach ($cNodes as $cNode) {
        if ($cNode->nodeName == '#text') {
          $text = $cNode->nodeValue;
        }
      }
      $element->addListItem($text, $data['listdepth'], $styles['font'], $styles['list'], $styles['paragraph']);
    }

    return null;
  }

  /**
   * Parse span
   * 
   * Changes the inline style when a Span element is found.
   * 
   * @param type $node
   * @param type $element
   * @param array $styles
   * @return type
   */
  private static function parseSpan($node, &$styles)
  {
    $styles['font'] = self::parseInlineStyle($node, $styles['font']);
    return null;
  }

  /**
   * Parse style
   *
   * @param \DOMAttr $attribute
   * @param array $styles
   * @return array
   */
  private static function parseStyle($attribute, $styles)
  {
    $properties = explode(';', trim($attribute->value, " \t\n\r\0\x0B;"));
    foreach ($properties as $property) {
      list($cKey, $cValue) = explode(':', $property, 2);
      $cValue = trim($cValue);
      switch (trim($cKey)) {
        case 'text-decoration':
          switch ($cValue) {
            case 'underline':
              $styles['underline'] = 'single';
              break;
            case 'line-through':
              $styles['strikethrough'] = true;
              break;
          }
          break;
        case 'text-decoration-line':
          switch ($cValue) {
            case 'underline':
              $styles['underline'] = 'single';
              break;
            case 'line-through':
              $styles['strikethrough'] = true;
              break;
          }
          break;
        case 'text-align':
          $styles['align'] = $cValue;
          break;
        case 'display':
          $styles['hidden'] = $cValue === 'none';
          break;
        case 'direction':
          $styles['rtl'] = $cValue === 'rtl';
          break;
          // added to handled inline Span style font size changes.
        case 'font-size':
          $styles['size'] = substr($cValue, 0, -2); // substr used to remove the px from the html string size string
          break;
        case 'font-family':
          $cValue = array_map('trim', explode(',', $cValue));
          $styles['name'] = ucwords($cValue[0]);
          break;
          // added to handled inline Span color changes.
        case 'color':
          if (strpos($cValue, 'rgb') !== false) {
            $cValue = str_replace("rgb(", "", $cValue);
            $cValue = str_replace(")", "", $cValue);
            $aValue = explode(",", $cValue);
            foreach ($aValue as &$v) {
              $v = base_convert(trim($v), 10, 16);
              if (strlen($v) < 2) {
                $v = "0" . $v;
              }
            }
            $cValue = implode("", $aValue);
          }
          $styles['color'] = trim($cValue, "#"); //must use hex colors
          break;
        case 'background-color':
          $styles['bgColor'] = trim($cValue, "#"); //must use hex colors
          break;
        case 'line-height':
          $matches = array();
          if (preg_match('/([0-9]+\.?[0-9]*[a-z]+)/', $cValue, $matches)) {
            //matches number with a unit, e.g. 12px, 15pt, 20mm, ...
            $spacingLineRule = \PhpOffice\PhpWord\SimpleType\LineSpacingRule::EXACT;
            $spacing = Converter::cssToTwip($matches[1]);
          } elseif (preg_match('/([0-9]+)%/', $cValue, $matches)) {
            //matches percentages
            $spacingLineRule = \PhpOffice\PhpWord\SimpleType\LineSpacingRule::AUTO;
            //we are subtracting 1 line height because the Spacing writer is adding one line
            $spacing = ((((int) $matches[1]) / 100) * Paragraph::LINE_HEIGHT) - Paragraph::LINE_HEIGHT;
          } else {
            //any other, wich is a multiplier. E.g. 1.2
            $spacingLineRule = \PhpOffice\PhpWord\SimpleType\LineSpacingRule::AUTO;
            //we are subtracting 1 line height because the Spacing writer is adding one line
            $spacing = ($cValue * Paragraph::LINE_HEIGHT) - Paragraph::LINE_HEIGHT;
          }
          $styles['spacingLineRule'] = $spacingLineRule;
          $styles['line-spacing'] = $spacing;
          break;
        case 'letter-spacing':
          $styles['letter-spacing'] = Converter::cssToTwip($cValue);
          break;
        case 'text-indent':
          $styles['indentation']['firstLine'] = Converter::cssToTwip($cValue);
          break;
        case 'font-weight':
          $tValue = false;
          if (preg_match('#bold#', $cValue)) {
            $tValue = true; // also match bolder
          }
          $styles['bold'] = $tValue;
          break;
        case 'font-style':
          $tValue = false;
          if (preg_match('#(?:italic|oblique)#', $cValue)) {
            $tValue = true;
          }
          $styles['italic'] = $tValue;
          break;
        case 'margin-top':
          $styles['spaceBefore'] = Converter::cssToPoint($cValue);
          break;
        case 'margin-bottom':
          $styles['spaceAfter'] = Converter::cssToPoint($cValue);
          break;
        case 'border-color':
          $styles['color'] = trim($cValue, '#');
          break;
        case 'border-width':
          $styles['borderSize'] = Converter::cssToPoint($cValue);
          break;
        case 'border-style':
          $styles['borderStyle'] = self::mapBorderStyle($cValue);
          break;
        case 'width':
          if (preg_match('/([0-9]+[a-z]+)/', $cValue, $matches)) {
            $styles['width'] = Converter::cssToTwip($matches[1]);
            $styles['unit'] = \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP;
          } elseif (preg_match('/([0-9]+)%/', $cValue, $matches)) {
            $styles['width'] = $matches[1] * 50;
            $styles['unit'] = \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT;
          } elseif (preg_match('/([0-9]+)/', $cValue, $matches)) {
            $styles['width'] = $matches[1];
            $styles['unit'] = \PhpOffice\PhpWord\SimpleType\TblWidth::AUTO;
          }
          break;
        case 'border':
          if (preg_match('/([0-9]+[^0-9]*)\s+(\#[a-fA-F0-9]+)\s+([a-z]+)/', $cValue, $matches)) {
            $styles['borderSize'] = Converter::cssToPoint($matches[1]);
            $styles['borderColor'] = trim($matches[2], '#');
            $styles['borderStyle'] = self::mapBorderStyle($matches[3]);
          }
          break;
      }
    }

    return $styles;
  }

  /**
   * Parse image node
   *
   * @param \DOMNode $node
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   *
   * @return \PhpOffice\PhpWord\Element\Image
   **/
  private static function parseImage($node, $element)
  {
    $style = array();
    $src = null;
    foreach ($node->attributes as $attribute) {
      switch ($attribute->name) {
        case 'src':
          $src = $attribute->value;
          break;
        case 'width':
          $width = $attribute->value;
          $style['width'] = $width;
          $style['unit'] = \PhpOffice\PhpWord\Style\Image::UNIT_PX;
          break;
        case 'height':
          $height = $attribute->value;
          $style['height'] = $height;
          $style['unit'] = \PhpOffice\PhpWord\Style\Image::UNIT_PX;
          break;
        case 'style':
          $styleattr = explode(';', $attribute->value);
          foreach ($styleattr as $attr) {
            if (strpos($attr, ':')) {
              list($k, $v) = explode(':', $attr);
              switch (trim($k)) {
                case 'float':
                  if (trim($v) == 'right') {
                    $style['hPos'] = \PhpOffice\PhpWord\Style\Image::POS_RIGHT;
                    $style['hPosRelTo'] = \PhpOffice\PhpWord\Style\Image::POS_RELTO_PAGE;
                    $style['pos'] = \PhpOffice\PhpWord\Style\Image::POS_RELATIVE;
                    $style['wrap'] = \PhpOffice\PhpWord\Style\Image::WRAP_TIGHT;
                    $style['overlap'] = true;
                  }
                  if (trim($v) == 'left') {
                    $style['hPos'] = \PhpOffice\PhpWord\Style\Image::POS_LEFT;
                    $style['hPosRelTo'] = \PhpOffice\PhpWord\Style\Image::POS_RELTO_PAGE;
                    $style['pos'] = \PhpOffice\PhpWord\Style\Image::POS_RELATIVE;
                    $style['wrap'] = \PhpOffice\PhpWord\Style\Image::WRAP_TIGHT;
                    $style['overlap'] = true;
                  }
                  break;
                case 'width':
                  $style['width'] = (int) (trim($v));
                  $style['unit'] = \PhpOffice\PhpWord\Style\Image::UNIT_PX;
                  break;
                case 'height':
                  $style['height'] = (int) (trim($v));
                  $style['unit'] = \PhpOffice\PhpWord\Style\Image::UNIT_PX;
                  break;
              }
            }
          }
          break;
      }
    }
    $originSrc = $src;
    if (strpos($src, 'data:image') !== false) {
      $tmpDir = Settings::getTempDir() . '/';
      $match = array();
      preg_match('/data:image\/(\w+);base64,(.+)/', $src, $match);
      $src = $imgFile = $tmpDir . uniqid() . '.' . $match[1];
      $ifp = fopen($imgFile, 'wb');
      if ($ifp !== false) {
        fwrite($ifp, base64_decode($match[2]));
        fclose($ifp);
      }
    }
    $src = urldecode($src);
    //        if (!is_file($src)
    //            && !is_null(self::$options)
    //            && isset(self::$options['IMG_SRC_SEARCH'])
    //            && isset(self::$options['IMG_SRC_REPLACE'])) {
    //            $src = str_replace(self::$options['IMG_SRC_SEARCH'], self::$options['IMG_SRC_REPLACE'], $src);
    //        }
    if (!is_file($src)) {
      if ($imgBlob = @file_get_contents($src)) {
        $tmpDir = Settings::getTempDir() . '/';
        $match = array();
        preg_match('/.+\.(\w+)$/', $src, $match);
        $src = $tmpDir . uniqid() . '.' . $match[1];
        $ifp = fopen($src, 'wb');
        if ($ifp !== false) {
          fwrite($ifp, $imgBlob);
          fclose($ifp);
        }
      }
    }
    if (is_file($src)) {
      $newElement = $element->addImage($src, $style);
    } else {
      $newElement = $element->addText("Экспорт изображений в Word недоступен");
      // throw new \Exception("Could not load image $originSrc");
    }
    return $newElement;
  }

  /**
   * Parse line break
   *
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   */
  private static function parseLineBreak($element)
  {
    $element->addTextBreak();
  }
  /**
   * Parse link node
   *
   * @param \DOMNode $node
   * @param \PhpOffice\PhpWord\Element\AbstractContainer $element
   * @param array $styles
   */
  private static function parseLink($node, $element, &$styles)
  {
    $target = null;
    foreach ($node->attributes as $attribute) {
      switch ($attribute->name) {
        case 'href':
          $target = $attribute->value;
          break;
      }
    }
    $styles['font'] = self::parseInlineStyle($node, $styles['font']);
    if (strpos($target, '#') === 0) {
      return $element->addLink(substr($target, 1), $node->textContent, $styles['font'], $styles['paragraph'], true);
    }
    return $element->addLink($target, $node->textContent, $styles['font'], $styles['paragraph']);
  }

  /**
   * Parse table cell
   *
   * @param \DOMNode $node
   * @param \PhpOffice\PhpWord\Element\Table $element
   * @param array &$styles
   * @return \PhpOffice\PhpWord\Element\Cell|\PhpOffice\PhpWord\Element\TextRun $element
   */
  private static function parseCell($node, $element, &$styles)
  {
    $cellStyles = self::recursiveParseStylesInHierarchy($node, $styles['cell']);
    $colspan = $node->getAttribute('colspan');
    if (!empty($colspan)) {
      $cellStyles['gridSpan'] = $colspan - 0;
    }
    $cell = $element->addCell(null, $cellStyles);
    if (self::shouldAddTextRun($node)) {
      return $cell->addTextRun(self::parseInlineStyle($node, $styles['paragraph']));
    }
    return $cell;
  }
}
