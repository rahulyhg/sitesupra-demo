<?php

namespace Supra\FileStorage\UploadFilter;

class MimeTypeUploadFilter implements FileValidationInterface
{
	/**
	 * Validates file mimetype
	 * @param \Supra\FileStorage\Entity\File $file 
	 */
	public function validateFile(\Supra\FileStorage\Entity\File $file)
	{
		$result = $this->checkList($file->getMimeType());
		if( ! $result) {
			$message = 'File mimetype "'.$file->getMimeType().'" is not allowed';
			throw new UploadFilterException($message);
			\Log::error($message);
		}
	}

}
