<?php

# Pagination class
class pagination
{
	# Specify available arguments as defaults or as NULL (to represent a required argument)
	private $defaults = array (
		'paginationRecordsPerPageDefault'	=> 50,
		'paginationRecordsPerPagePresets'	=> array (50, 100, 250, 1000, 2000, 10000),
		'cookieName' => 'recordsperpage',
		
	);
	
	# Class properties
	private $html  = '';
	
	
	# Constructor
	public function __construct ($settings, $baseUrl)
	{
		# Load required libraries
		require_once ('application.php');
		require_once ('ultimateForm.php');
		
		# Merge in the arguments; note that $errors returns the errors by reference and not as a result from the method
		if (!$this->settings = application::assignArguments ($errors, $settings, $this->defaults, __CLASS__, NULL, $handleErrors = true)) {return false;}
		
		# Assign the baseUrl
		$this->baseUrl = $baseUrl;
		
	}
	
	
	# Function to get the current page
	public function currentPage ()
	{
		# Determine the page
		$page = 1;
		if (isSet ($_GET['page']) && ctype_digit ($_GET['page'])) {
			$page = $_GET['page'];
		}
		
		# Return the page
		return $page;
	}
	
	
	# Function to enable pagination - based on www.phpnoise.com/tutorials/9/1
	public /* static */ function getPagerData ($items, $limitPerPage, $page)
	{
		# Take the number of items
		$items = (int) $items;
		
		# Ensure the limit is at least 1
		$limitPerPage = max ((int) $limitPerPage, 1);
		
		# Ensure the page is at least 1
		$page = max ((int) $page, 1);
		
		# Get the total number of pages (items divided by the number of pages, rounded up to catch the last (potentially incomplete) page)
		$totalPages = ceil ($items / $limitPerPage);
		
		# Ensure the page is no more than the number of pages
		$page = min ($page, $totalPages);
		
		# Define the offset, taking page 1 (rather than 0) as the first page
		$offset = ($page - 1) * $limitPerPage;
		if ($offset < 0) {$offset = 0;}	// Prevent a negative offset if the page is 0 (i.e. due to no results)
		
		# Return the result
		return array ($totalPages, $offset, $items, $limitPerPage, $page);
	}
	
	
	# Pagination links
	public /* static */ function paginationLinks ($page, $totalPages, $baseLink, $queryString = '' /* i.e. the complete string, e.g. foo=bar&person=john */, $ulClass = 'paginationlinks', $pageInQueryString = false)
	{
		# Load required libraries
		require_once ('application.php');
		require_once ('pureContent.php');
		
		# Avoid creating pagination if there is only one page
		if ($totalPages == 1) {return '';}
		
		# Determine a pattern for the link, and a special-case link for page 1 (which has no page number added)
		$linkFormat = $baseLink . ($pageInQueryString ? '?page=%s' . ($queryString ? '&amp;' . htmlspecialchars ($queryString) : '') : 'page%s.html' . ($queryString ? '?' . htmlspecialchars ($queryString) : ''));
		$linkFormatPage1 = $baseLink . ($queryString ? '?' . htmlspecialchars ($queryString) : '');
		
		# Create a jumplist
		$current = ($page == 1 ? $linkFormatPage1 : preg_replace ('/%s/', $page, $linkFormat, 1));	// Use of preg_replace here is replacement for sprintf: safely ignores everything after the first %s, using the limit=1 technique as per http://stackoverflow.com/questions/4863863
		$pages = array ();
		for ($i = 1; $i <= $totalPages; $i++) {
			$link = ($i == 1 ? $linkFormatPage1 : preg_replace ('/%s/', $i, $linkFormat, 1));
			$pages[$link] = "Page {$i} <span class=\"faded\">of {$totalPages}</span>";
		}
		$jumplist = pureContent::htmlJumplist ($pages, $current, $baseLink, $name = 'jumplist', $parentTabLevel = 0, $class = 'jumplist', $introductoryText = '');
		
		# Create pagination HTML
		$paginationLinks['introduction'] = 'Switch page: ';
		$paginationLinks['start'] = (($page != 1) ? '<a href="' . $linkFormatPage1 . '">&laquo;</a>' : '<span class="faded">&laquo;</span>');
		$paginationLinks['previous'] = (($page > 1) ? '<a href="' . ($page == 2 ? $linkFormatPage1 : preg_replace ('/%s/', ($page - 1), $linkFormat, 1)) . '">&lt;</a>' : '<span class="faded">&lt;</span>');
		$paginationLinks['root'] = $jumplist;
		$paginationLinks['next'] = (($page < $totalPages) ? '<a href="' . preg_replace ('/%s/', ($page + 1), $linkFormat, 1) . '">&gt;</a>' : '<span class="faded">&gt;</span>');
		$paginationLinks['end'] = (($page != $totalPages) ? '<a href="' . preg_replace ('/%s/', $totalPages, $linkFormat, 1) . '">&raquo;</a>' : '<span class="faded">&raquo;</span>');
		
		# Compile the HTML
		$html = application::htmlUl ($paginationLinks, 0, $ulClass, true, false, false, $liClass = true);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create a form to set the number of pagination records per page
	public function recordsPerPageForm (&$html)
	{
		# Determine the number of pagination records per page
		$recordsPerPage = $this->settings['paginationRecordsPerPageDefault'];
		
		# Determine the available preset values
		$presets = $this->settings['paginationRecordsPerPagePresets'];
		$presets[] = $this->settings['paginationRecordsPerPageDefault'];
		$presets = array_unique ($presets);
		sort ($presets);
		
		# Convert values to labels
		$values = array ();
		foreach ($presets as $preset) {
			$values[$preset] = number_format ($preset) . ' per page';
		}
		
		# Read the cookie if it exists; unsetting it if the value is invalid
		$cookieName = $this->settings['cookieName'];
		if (isSet ($_COOKIE[$cookieName])) {
			$recordsPerPage = $_COOKIE[$cookieName];
			if (!isSet ($values[$recordsPerPage])) {
				unset ($_COOKIE[$cookieName]);
				$recordsPerPage = $this->settings['paginationRecordsPerPageDefault'];
			}
		}
		
		# Create the form
		$html = '';
		$form = new form (array (
			'formCompleteText' => false,
			'name' => false,
			'div' => 'right',
			'requiredFieldIndicator' => false,
			'display' => 'template',
			'submitButtonAccesskey' => false,
			'submitButtonText' => 'Go!',
			'reappear' => true,
			'displayTemplate' => '{[[PROBLEMS]]}{recordsperpage}{[[SUBMIT]]}</p>',
		));
		$form->select (array (
			'name'	=> 'recordsperpage',
			'title'	=> false,
			'required' => true,
			'values' => $values,
			'nullText' => false,
			'default' => $recordsPerPage,
			'onchangeSubmit' => true,
			'nullRequiredDefault' => false,
		));
		if ($result = $form->process ($html)) {
			$recordsPerPage = $result['recordsperpage'];
			
			# Set a cookie with this value
			$thirtyDays = 7 * 24 * 60 * 60;
			setcookie ($cookieName, $recordsPerPage, time () + $thirtyDays, $this->baseUrl . '/', $_SERVER['SERVER_NAME']);
			
			# Refresh the page
			$html = application::sendHeader ('refresh', false, $redirectMessage = true);
		}
		
		# Return the result
		return $recordsPerPage;
	}
	
}

?>