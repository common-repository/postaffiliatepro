<?php
/**
 *   @copyright Copyright (c) 2007 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpPostAffiliateProPlugin
 *   @since version 1.0.0
 *
 *   Licensed under GPL2
 */

abstract class postaffiliatepro_Form_Base extends postaffiliatepro_Base {

    const TYPE_FORM = 'default'; // remove
    const TYPE_TEMPLATE = 'template'; // remove

    private $formName;
    private $settings;
    protected $variables = array('infoMessages' => '', 'errorMessages' => '');

    public function __construct($name = '', $action = '') {
        $this->formName = $name;
        $this->addVariable('form.head', '<form name="'.$name.'" action="'.$action.'" method="post">');
        $this->addVariable('form.tail', '</form>');
    }

    private function loadSettingsString($name) {
        $this->addVariable('settings',"<input type='hidden' name='option_page' value='".
          esc_attr($name)."' /><input type='hidden' name='action' value='update' />".
          wp_nonce_field("$name-options",'_wpnonce', true, false));
    }

    protected abstract function initForm();

    protected abstract function getTemplateFile();

    protected function addSubmit($value = 'Save changes', $class = 'button-primary') {
        $this->addVariable('submit', '<input type="submit" id="submit" name="submit" value="'.$value.'" class="'.$class.'">');
    }

    protected function addHtml($name, $code) {
        $this->addVariable($name, $code);
    }

    protected function getOption($name, $type = 'text') {
        $result = get_option($name);
        if ($type == 'none') {
            return esc_attr($result);
        }
        if ($type == 'url') {
            $newResult = esc_url($result);
            if (substr($newResult, -1) !== '/') {
                $newResult .= '/';
            }
        } else {
            $newResult = sanitize_text_field($result);
        }
        if ($newResult != $result) {
            update_option($name, $newResult);
        }
        return esc_attr($newResult);
    }

    protected function addCheckbox($name, $templateName = null, $additionalCode = '') {
        $checked = '';
        if ($this->getOption($name) === 'true') {
            $checked = ' checked';
        }
        if ($templateName === null) {
            $templateName = $name;
        }
        $this->addVariable($templateName, '<input type="checkbox" name="'.$name.'" id="'.str_replace(array(' ', '[', ']'), array('_', ''), $name.'_').'" value="true"'.$checked.' '.$additionalCode.' class="checkbox"></input>');
    }

    protected function addSelect($name, $options) {
        $this->addVariable($name, "<select id='$name' name='$name'>\n".$this->getHTMLSelectOptions($options, $name)."</select>\n");
    }

    private function getHTMLSelectOptions($options, $name) {
        //options = assoc. arr, key(value) and value(name) of select option
        $html = '';
        $selected = $this->getOption($name, 'none');
        foreach ($options as $optionKey => $optionName) {
            $html .= '<option value="'.$optionKey.'"'.(($selected == $optionKey)?' selected="selected">':'>').$optionName."</option>\n";
        }
        return $html;
    }

    protected function addPassword($name, $size = 20) {
        $this->addVariable($name, '<input type="password" id="'.$name.'" name="'.$name.'" value="'.$this->getOption($name, 'none').'" class="password" onfocus="this.className = \'password-focus\'" onblur="this.className = \'password\'" size="'.$size.'">');
    }

    protected function addTextBox($name, $size = 20, $value = '') {
        $useValue = $this->getOption($name);
        if ($useValue === '') {
            $useValue = $value;
        }
        $this->addVariable($name, '<input type="text" id="'.$name.'" name="'.$name.'" value="'.$useValue.'" class="text" onfocus="this.className = \'text-focus\'" onblur="this.className = \'text\'" size="'.$size.'">');
    }

    protected function addUrlTextBox($name, $size = 20) {
        $value = $this->getOption($name, 'url');
        $this->addVariable($name, '<input type="text" id="'.$name.'" name="'.$name.'" value="'.$value.'" class="text" onfocus="this.className = \'text-focus\'" onblur="this.className = \'text\'" size="'.$size.'">');
    }

    public function render($toVar = false, $template = '') {
        $this->initForm();
        if ($template == '') {
            $html = file_get_contents($this->getTemplateFile());
            if ($this->formName != '') {
                $this->loadSettingsString($this->formName);
            }
        } else {
            $html = file_get_contents($template);
        }

        foreach ($this->variables as $name => $value) {
            $html = str_replace('{'.$name.'}', $value, $html);
        }
        if ($toVar) {
            return $html;
        }
        echo '<div class="postaffiliatepro">' . $html . '</div>';
    }

    protected function addVariable($name, $value) {
        if (isset($this->variables[$name])) {
            $this->variables[$name] .= $value;
        } else {
            $this->variables[$name] = $value;
        }
    }
}