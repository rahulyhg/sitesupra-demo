<?php

namespace Supra\Editable;

/**
 * String editable content
 */
class InlineString extends EditableAbstraction
{
	const EDITOR_TYPE = 'InlineString';
	const EDITOR_INLINE_EDITABLE = true;
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}
	
	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return static::EDITOR_INLINE_EDITABLE;
	}
	
}
