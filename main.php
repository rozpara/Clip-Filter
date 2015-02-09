<?php

	ini_set("auto_detect_line_endings", true); //Because Mac :(

	//File names
	$CLIPS_FILE = 'clips.csv';
	$VALID_FILE = 'valid.csv';
	$INVALID_FILE = 'invalid.csv';

	class ClipFilter extends FilterIterator {

		//Track the valid invalid clips as well so I don't need to iterate through the
		//data set twice nor do I have to write two separate filters
		private $invalidClips;
		private $validClips;

		/**
		 * @constructor
		 */
		public function __construct(Iterator $iter ){
			parent::__construct($iter);
			$this->invalidClips = array();
			$this->validClips = array();
		}

		/**
		 * @override
		 */
		public function accept(){
		 	$curr = $this->getInnerIterator()->current();
		 	//Constraints
		 	if(strlen($curr['title']) < 30 && $curr['privacy'] === 'anybody' 
		 		&& $curr['total_likes'] > 10 && $curr['total_plays'] > 200) {
		 		$this->validClips[] = $curr;
		 		return true;
		 	}
		 	$this->invalidClips[] = $curr;
		 	return false;
		}

		/**
		 * Returns an array of the elements that failed to pass the filter
		 */
		public function getInvalidClips(){
			return $this->invalidClips;
		}

		/**
		 * Returns an array of the elements that passed the filter
		 */
		public function getValidClips(){
			return $this->validClips;
		}
	}

	//Just check if any of the files are missing/readable/writable before begining operations
	//We want to terminate the program if something isn't right.
	if(!(file_exists("./$CLIPS_FILE") && file_exists("./$VALID_FILE") && file_exists("./$INVALID_FILE"))) {
		exit("Please make sure you have all required files in your directory: ($CLIPS_FILE, $VALID_FILE, $INVALID_FILE)");
	}
	is_readable($CLIPS_FILE) or exit("$CLIPS_FILE is not readable. Please change permissions accordingly.");
	is_writable($VALID_FILE) or exit("$VALID_FILE is not writable. Please change permissions accordingly.");
	is_writable($INVALID_FILE) or exit("$INVALID_FILE is not writable. Please change permissions accordingly.");

	$handle = fopen("./$CLIPS_FILE", 'r');
	$keys = fgetcsv($handle); //Returns the header keys
	$data = array();

	//Create an object array for each entry. I would have created a Clip class object but I wouldn't really
	//gain anything from that unless it was nessasary to maintain and/or change state; which isn't nessasary here.
	//Pretty overkill for what needs to be done...also this is easier.
	while($row = fgetcsv($handle, 1024, ",")) {
		$data[] = array_combine($keys, $row);
	}

	fclose($handle);

	//Initiate the filter
	$filter = new ClipFilter(new ArrayIterator($data));

	//Write to the csv valid csv file
	$handle = fopen("./$VALID_FILE", 'w');
	foreach ($filter as $key => $value) {
		fputcsv($handle, array($value['id']));
	}

	fclose($handle);

	//Write to the invalid csv file
	$handle = fopen("./$INVALID_FILE", 'w');
	foreach ($filter->getInvalidClips() as $key => $value) {
		fputcsv($handle, array($value['id']));
	}

	fclose($handle);

?>