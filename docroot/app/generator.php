<?php

class Generator {

	protected $salt = "magical";

	protected $debug = true;
	protected $image = null;
	protected $instructions = array();

	public function __construct($string) {

		$sets[] = md5($string);
		$sets[] = md5($string . $this->salt);

		// a 32 character string
		foreach($sets as $current_set) {

			for ($i = 0; $i < 8; $i++) {
				$string = substr($current_set, $i * 4, 4);
				$this->instructions[] = new Instruction( $string );
			}

		}

	}

	public function output($x = 512, $y = 512) {

		$image = imagecreate($x, $y);

		$previous_instruction = null;

		for ($i = 0; $i < count($this->instructions); $i++) {
			$this->instructions[$i]->apply( $image, $previous_instruction );
			$previous_instruction = $this->instructions[$i];
		}

		header("Content-type: image/jpeg");
		imagejpeg($image, null, 100);

	}

	public function log($str) {
		if ($this->debug === true)
			echo "$str\n";
	}

}

class Instruction {

	protected $source = "0000";

	public $x;
	public $y;
	public $color;
	public $type;
	public $value;

	protected $types = array(
	
		// lines, polygons etc
		0	=> "filledCircleAt",
		1	=> "circleAt",
		2	=> "dashedLineTo",
		3	=> "lineTo",

		// "brushes"
		4	=> "circleTo",
		5	=> "circleTo",
		6	=> "circleTo",
		7	=> "circleTo",
		8	=> "circleTo",
		9	=> "circleTo",
		10	=> "circleTo",
		11	=> "circleTo",
		12	=> "circleTo",

		// filters
		13	=> "smooth",
		14	=> "edges",
		15	=> "blur"
	);

	protected $positions = array(
		0	=> array(1,1),
		1	=> array(1,2),
		2	=> array(1,3),
		3	=> array(1,4),
		4	=> array(2,1),
		5	=> array(2,2),
		6	=> array(2,3),
		7	=> array(2,4),
		8	=> array(3,1),
		9	=> array(3,2),
		10	=> array(3,3),
		11	=> array(3,4),
		12	=> array(4,1),
		13	=> array(4,2),
		14	=> array(4,3),
		15	=> array(4,4)
	);
	
	protected $colors = array(
		0	=> array(255,0,0,127),
		1	=> array(200,0,0,127),
		2	=> array(150,0,0,127),
		3	=> array(100,0,122,127),
		4	=> array(255,255,0,127),
		4	=> array(100,200,0,127),
		5	=> array(0,150,0,127),
		6	=> array(70,0,255,20),
		7	=> array(80,255,0,20),
		8	=> array(90,255,0,20),
		9	=> array(100,0,0,20),
		10	=> array(255,100,0,20),
		11	=> array(120,0,122,20),
		12	=> array(130,255,0,20),
		13	=> array(140,0,255,20),
		14	=> array(150,255,0,20),
		15	=> array(160,122,0,20)
	);

	public function __construct($instruction) {
		if (strlen($instruction) != 4)
			throw new InstructionException("Invalid Instruction Length");

		$this->source = $instruction;

		$position = hexdec(substr($instruction,0,1));

		$this->x = $this->positions[$position][0];
		$this->y = $this->positions[$position][1];

		$this->color = hexdec(substr($instruction,1,1));
		$this->type = hexdec(substr($instruction,2,1));
		$this->value = hexdec(substr($instruction,3,1));
	}

	public function apply(&$image, $previous_instruction = null) {

		$color = imagecolorallocatealpha(
		$image,$this->colors[ $this->color ][0], $this->colors[ $this->color ][1], $this->colors[ $this->color ][2], $this->colors[ $this->color ][3]);

		$scale_x = imagesx($image);
		$scale_y = imagesy($image);

		$x = $this->x / 4 * $scale_x;
		$y = $this->y / 4 * $scale_y;

		$previous_x = (isset($previous_instruction->x) ? $previous_instruction->x : 0) / 4 * $scale_x;
		$previous_y = (isset($previous_instruction->y) ? $previous_instruction->y : 0) / 4 * $scale_y;

		switch($this->types[$this->type]) {

			default:
			case "circleAt":
				$value = $this->value / 16 * $scale_x;
				imageellipse($image, $x, $y, $value, $value, $color);
				break;

			case "filledCircleAt":
				$value = $this->value / 16 * $scale_x;
				imagefilledellipse($image, $x, $y, $value, $value, $color);
				break;

			case "lineTo":
				$value = $this->value;
				$brush = imagecreatetruecolor($value,$value);
				imagefilledellipse($brush, $value / 2, $value / 2, $value, $value, $color);
				imagesetbrush($image,$brush);
				imageline($image, $x, $y, $previous_x, $previous_y, IMG_COLOR_BRUSHED);
				break;

			case "circleTo":
				$value = 2 * $this->value + 2;
				$brush = imagecreatetruecolor($value,$value);
				imagefilledellipse($brush, $value / 2, $value / 2, $value, $value, $color);
				imagesetbrush($image,$brush);
				imageline($image, $x, $y, $previous_x, $previous_y, IMG_COLOR_BRUSHED);
				break;

			case "dashedLineTo":
				$value = $this->value;
				for ($i = 0; $i <= $value; $i++)
					imagedashedline($image, $x, $y + $i, $previous_x, $previous_y + $i, $color);
				for ($i = 0; $i <= $value; $i++)
					imagedashedline($image, $x + $i, $y, $previous_x + $i, $previous_y, $color);
				break;

			case "blur":
				imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
				break;

			case "edges":
				imagefilter($image, IMG_FILTER_EDGEDETECT);
				break;

			case "smooth":
				$value = $this->value / 16 * 2;
				imagefilter($image, IMG_FILTER_SMOOTH, $value);
				break;
		}

		return $image;
	}

	public function __toString() {
		return print_r( array(
			"x" => $this->x,
			"y" => $this->y,
			"color" => $this->color,
			"type" => $this->type
		), true );
	}

}
