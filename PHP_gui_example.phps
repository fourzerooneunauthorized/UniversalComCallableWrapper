<?php
/*
Container PHP class
Example to show how to use Universal_CCW compiled library in PHP command line interface
Wraps Universal_CCW_Container and Universal_CCW_Factory COM object to allow more natual program writing

*/

Class Container {
	public static $COM;
	private $Container_obj;
	
	function __construct($item_id, $asmb_long_name = null, $full_class_name = null, $is_static = false) {
		if (self::$COM === null) {
			// set shared referenced to COM api factory class
			self::$COM = new COM("Universal_CCW.Universal_CCW_Factory");
			}
		if (is_object($item_id)) {
			// if being passed a Universal_CCW_Container COM object, wrap it in a new Container PHP object
			if ($item_id instanceof VARIANT AND self::$COM->Type_Name($item_id) === "Universal_CCW_Container") {
				$this->Container_obj = $item_id;
				}
			else {
				throw new Exception("__construct: unknown object type.");
				}
			}
		else {
			if ($is_static) {
				$this->Container_obj = self::$COM->New_Static($asmb_long_name, $full_class_name);
				}
			else {
				$this->Container_obj = self::$COM->New_Object($item_id, $asmb_long_name, $full_class_name);
				}
			}
		}

	function __destruct() {
		$this->Container_obj->Destroy();
		}
		
	function __set($prop, $val) {
		if ($val instanceof Container) {
			// reference underlying Universal_CCW_Container COM object instead to work with this method
			$val = $val();
			}
		$this->Container_obj->Set_Property_Value($prop, $val);
		}
		
	function __get($prop) {
		$return = $this->Container_obj->Get_Property_Value($prop);
		// if return value is Universal_CCW_Container COM object, wrap it in a new Container PHP object
		if ($return instanceof VARIANT AND self::$COM->Type_Name($return) === "Universal_CCW_Container") {
			return new Container($return);
			}
		else {
			return $return;
			}		
		}

	function __call($fnc, $args) {
		array_unshift($args, $fnc);
		foreach ($args as $key => &$arg) {
			// reference underlying Universal_CCW_Container COM objects instead (if any) to work with this method
			if ($arg instanceof Container) {
				$arg = $arg();
				}
			}
		$return = call_user_func_array(array($this->Container_obj, "Call_Method"), $args);
		// if return value is Universal_CCW_Container COM object, wrap it in a new Container PHP object
		if ($return instanceof VARIANT AND self::$COM->Type_Name($return) === "Universal_CCW_Container") {
			return new Container($return);
			}
		else {
			return $return;
			}
		}
	
	function __toString() {
		return $this->Container_obj->My_Handle;
		}
		
	function Subscribe_To_Event($event_name) {
		$this->Container_obj->Subscribe_To_Event($event_name);
		}
		
	function __invoke() {
		// reference underlying Universal_CCW_Container COM object
		return $this->Container_obj;
		}
	}

	
$namespace = "System.Windows.Forms";
$asmb_full_name = 'System.Windows.Forms, Version=2.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089';

// Main Form
$form = new Container("form1", $asmb_full_name, $namespace.".Form");
$form->Width = 550;
$form->Height = 550;
$form->AutoScroll = true;
$form->Text = "A form";
$form->Subscribe_To_Event("Closing");

// Button 1
$btn1 = new Container("btn1", $asmb_full_name, $namespace.".Button");
$btn1->Width = 75;
$btn1->Height = 25;
$btn1->Top = 25;
$btn1->Left = 5;
$btn1->Text = "A Button";
$form->Controls->Add($btn1);
$btn1->Subscribe_To_Event("Click");

// Textbox 1
$txt1 = new Container("txt1", $asmb_full_name, $namespace.".TextBox");
$txt1->Width = 400;
$txt1->Height = 300;
$txt1->Top = 50;
$txt1->Left = 5;
$txt1->Multiline = True;
$txt1->ScrollBars = 3;
$txt1->Text = "Demo color-picker application spawned by ".Container::$COM->Calling_App_Name."\r\n";
$form->Controls->Add($txt1);
$txt1->Subscribe_To_Event("TextChanged");

$form->Show();

while(true) {
	// Main app loop
	com_message_pump();
	if (Container::$COM->Pending_Message_Count > 0) {
		$event = Container::$COM->Consume_Message();
		if ($event["event"] === "Closing" AND $event["source"] === (string) $form) {
			// if closing form, exit loop
			break;
			}
		else if ($event["event"] === "Click" AND $event["source"] === (string) $btn1) {
			//  popup a color-picker form
			$fd = new Container("fd", $asmb_full_name, $namespace.".ColorDialog");
			$fd->ShowDialog();
			// print chosen color value in textbox
			$txt1->Text .= "\r\n".$fd->Color;
			$fd = null;
			}

		}
	}
echo "App ended.";

// wait for command line input before exitting
fgets(STDIN);

?>