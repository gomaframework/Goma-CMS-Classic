<?php
defined("IN_GOMA") OR die();

/**
 * An phone-number text field.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.0
 */
class PhoneNumberField extends TextField {
    /**
     * this function generates some JavaScript for the validation of the field
     * @name jsValidation
     * @access public
     * @return string
     */
    public function jsValidation() {
        return '
                var regexp = /^(\+\d{1,2}\s)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/;
                if(regexp.test($("#' . $this->ID() . '").val()))
                {

                } else
                {
                        $("#' . $this->divID() . '").find(".err").remove();
                        $("#' . $this->ID() . '").after("<div class=\"err\" style=\"color: #ff0000;\">' . lang("form_phone_not_valid", "Please enter a correct phone-number.") . '</div>");
                        return false;
                }
            ';
    }

    /**
     * this is the validation for this field if it is required
     *
     * @name validation
     * @access public
     * @return bool|string
     */
    public function validate($value) {
        if(RegexpUtil::isPhoneNumber($value)) {
            return true;
        } else {
            return lang("form_phone_not_valid", "Please enter a correct phone-number.");
        }
    }
}
