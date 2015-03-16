<?php

class DevHelper_Helper_Phrase
{
	protected static $_lookingForPhraseTitles = false;
	protected static $_foundPhraseTitles = array();

	public static function startLookingForPhraseTitles()
	{
		self::$_lookingForPhraseTitles = true;
		self::$_foundPhraseTitles = array();
	}

	public static function finishLookingForPhraseTitles($addOnPhrases, XenForo_Model_Phrase $phraseModel)
	{
		static $whitelistedPrefixes = array(
			'admin_navigation_',
			'admin_permission_',
			'cron_entry_',
			'option_',
			'permission_',
			'style_property_',
		);
		$foundPhraseTitles = DevHelper_Helper_Phrase::getFoundPhraseTitles();
		$usedPhraseTitles = array();
		$foundButUnusedPhraseTitles = array();

		foreach ($addOnPhrases AS $phrase)
		{
			$used = in_array($phrase['title'], $foundPhraseTitles);

			if (!$used)
			{
				foreach ($whitelistedPrefixes as $prefix)
				{
					if (strpos($phrase['title'], $prefix) === 0)
					{
						$used = true;
						break;
					}
				}
			}

			if (!$used)
			{
				echo "Add-on phrase not used: <span style='color: red'>{$phrase['title']}</span>\n";
			}
			else
			{
				$usedPhraseTitles[] = $phrase['title'];
			}
		}

		foreach ($foundPhraseTitles as $phraseTitle)
		{
			if (!in_array($phraseTitle, $usedPhraseTitles))
			{
				$foundButUnusedPhraseTitles[] = $phraseTitle;
			}
		}
		if (!empty($foundButUnusedPhraseTitles))
		{
			$foundButUnusedPhrases = $phraseModel->getPhrasesInLanguageByTitles($foundButUnusedPhraseTitles);
			foreach ($foundButUnusedPhraseTitles as $phraseTitle)
			{
				$good = true;

				if (empty($foundButUnusedPhrases[$phraseTitle]))
				{
					echo "Phrase not found: <span style='color: red'>{$phraseTitle}</span>\n";
					$good = false;
				}

				if ($good)
				{
					$foundButUnusedPhrase = $foundButUnusedPhrases[$phraseTitle];
					if (!empty($foundButUnusedPhrase['addon_id']) AND $foundButUnusedPhrase['addon_id'] !== 'XenForo')
					{
						echo "Phrase from another add-on: <span style='color: red'>{$phraseTitle}</span> ({$foundButUnusedPhrase['addon_id']})\n";
						$good = false;
					}
				}

				if (!$good)
				{
					$phraseUsages = self::getPhraseUsages($phraseTitle);
					foreach ($phraseUsages as $phraseUsagePath => $phraseUsageLine)
					{
						echo "    <span style='color: #ddd'>Used in {$phraseUsagePath}:{$phraseUsageLine}</span>\n";
					}
				}
			}
		}
	}

	public static function getFoundPhraseTitles()
	{
		return array_keys(self::$_foundPhraseTitles);
	}

	public static function getPhraseUsages($phraseTitle)
	{
		if (isset(self::$_foundPhraseTitles[$phraseTitle]))
		{
			return self::$_foundPhraseTitles[$phraseTitle];
		}
		else
		{
			return array();
		}
	}

	public static function parsePhpForPhraseTracking($path, $contents)
	{
		if (self::$_lookingForPhraseTitles == false)
		{
			return false;
		}

		$offset = 0;
		$newXenForoPhrase = 'new XenForo_Phrase(';
		while (true)
		{
			$strpos = strpos($contents, $newXenForoPhrase, $offset);

			if ($strpos !== false)
			{
				$offset = $strpos + strlen($newXenForoPhrase);
				$phraseTitle = DevHelper_Helper_Php::extractString($contents, $offset);

				if (is_string($phraseTitle))
				{
					self::$_foundPhraseTitles[$phraseTitle][$path] = substr_count(substr($contents, 0, $offset), "\n");
				}
				else
				{
					continue;
				}
			}
			else
			{
				break;
			}
		}
	}

	public static function parseXmlForPhraseTracking($path)
	{
		if (self::$_lookingForPhraseTitles == false)
		{
			return false;
		}

		$contents = file_get_contents($path);

		$offset = 0;
		while (true)
		{
			if (preg_match('/{xen:phrase (?<title>[^,}]+)(,|})/', $contents, $matches, PREG_OFFSET_CAPTURE, $offset))
			{
				$phraseTitle = $matches['title'][0];
				self::$_foundPhraseTitles[$phraseTitle][$path] = substr_count(substr($contents, 0, $offset), "\n");
				$offset = $matches[0][1] + strlen($matches[0][0]);
			}
			else
			{
				break;
			}
		}
	}

}
