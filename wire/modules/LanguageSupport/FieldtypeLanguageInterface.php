<?php

/**
 * Interface used to indicate that the Fieldtype supports multiple languages
 *
 */

interface FieldtypeLanguageInterface {
	public function languageAdded(Field $field, Page $language); 	
	public function languageRemoved(Field $field, Page $language); 	
}
