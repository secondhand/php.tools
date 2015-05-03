<?php
final class PSR1OpenTags extends FormatterPass {
	public function candidate($source, $foundTokens) {
		return true;
	}
	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';
		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;
			switch ($id) {
				case T_OPEN_TAG:
					if ('<?php' !== $text) {
						$this->appendCode('<?php' . ($this->hasLnAfter() || $this->hasLn($text) || $this->rightUsefulTokenIs(T_NAMESPACE) ? $this->newLine : $this->getSpace()));
						break;
					}
				case T_CLOSE_TAG:
					$tail = substr(trim($this->tkns[$index-1][1]), -1);
					if ($tail and $tail !== ';' and $tail !== ':') {
						$this->appendCode('; ' . $text);
					}else{
						$this->appendCode($text);
					}
					break;
				default:
					$this->appendCode($text);
					break;
			}
		}
		return $this->code;
	}
}
