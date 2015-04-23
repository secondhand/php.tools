<?php
final class GeneratePHPDoc extends AdditionalPass {
	public function candidate($source, $foundTokens) {
		if (isset($foundTokens[T_FUNCTION])) {
			return true;
		}

		return false;
	}
	public function format($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';
		$touchedVisibility = false;
		$touchedDocComment = false;
		$visibilityIdx = 0;
		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->getToken($token);
			$this->ptr = $index;
			switch ($id) {
				case T_DOC_COMMENT:
					$touchedDocComment = true;
					break;
				case T_FINAL:
				case T_ABSTRACT:
				case T_PUBLIC:
				case T_PROTECTED:
				case T_PRIVATE:
				case T_STATIC:
					if (!$this->leftTokenIs([T_FINAL, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT])) {
						$touchedVisibility = true;
						$visibilityIdx = $this->ptr;
					}
					break;
				case T_FUNCTION:
					if ($touchedDocComment) {
						$touchedDocComment = false;
						break;
					}
					$origIdx = $visibilityIdx;
					if (!$touchedVisibility) {
						$origIdx = $this->ptr;
					}
					list($ntId) = $this->getToken($this->rightToken());
					if (T_STRING != $ntId) {
						$this->appendCode($text);
						break;
					}
					$this->walkUntil(ST_PARENTHESES_OPEN);
					$paramStack = [];
					$tmp = ['type' => '', 'name' => ''];
					$count = 1;
					while (list($index, $token) = each($this->tkns)) {
						list($id, $text) = $this->getToken($token);
						$this->ptr = $index;

						if (ST_PARENTHESES_OPEN == $id) {
							++$count;
						}
						if (ST_PARENTHESES_CLOSE == $id) {
							--$count;
						}
						if (0 == $count) {
							break;
						}
						if (T_STRING == $id || T_NS_SEPARATOR == $id) {
							$tmp['type'] .= $text;
							continue;
						}
						if (T_VARIABLE == $id) {
							if ($this->rightTokenIs([ST_EQUAL]) && $this->walkUntil(ST_EQUAL) && $this->rightTokenIs([T_ARRAY])) {
								$tmp['type'] = 'array';
							}
							$tmp['name'] = $text;
							$paramStack[] = $tmp;
							$tmp = ['type' => '', 'name' => ''];
							continue;
						}
					}

					$returnStack = '';
					if (!$this->leftUsefulTokenIs(ST_SEMI_COLON)) {
						$this->walkUntil(ST_CURLY_OPEN);
						$count = 1;
						while (list($index, $token) = each($this->tkns)) {
							list($id, $text) = $this->getToken($token);
							$this->ptr = $index;

							if (ST_CURLY_OPEN == $id) {
								++$count;
							}
							if (ST_CURLY_CLOSE == $id) {
								--$count;
							}
							if (0 == $count) {
								break;
							}
							if (T_RETURN == $id) {
								if ($this->rightTokenIs([T_DNUMBER])) {
									$returnStack = 'float';
								} elseif ($this->rightTokenIs([T_LNUMBER])) {
									$returnStack = 'int';
								} elseif ($this->rightTokenIs([T_VARIABLE])) {
									$returnStack = 'mixed';
								} elseif ($this->rightTokenIs([ST_SEMI_COLON])) {
									$returnStack = 'null';
								}
							}
						}
					}

					$funcToken = &$this->tkns[$origIdx];
					$funcToken[1] = $this->renderDocBlock($paramStack, $returnStack) . $funcToken[1];
					$touchedVisibility = false;
			}
		}

		return implode('', array_map(function ($token) {
			list(, $text) = $this->getToken($token);
			return $text;
		}, $this->tkns));
	}

	private function renderDocBlock(array $paramStack, $returnStack) {
		if (empty($paramStack) && empty($returnStack)) {
			return '';
		}
		$str = '/**' . $this->newLine;
		foreach ($paramStack as $param) {
			$str .= rtrim(' * @param ' . $param['type']) . ' ' . $param['name'] . $this->newLine;
		}
		if (!empty($returnStack)) {
			$str .= ' * @return ' . $returnStack . $this->newLine;
		}
		$str .= ' */' . $this->newLine;
		return $str;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getDescription() {
		return 'Automatically generates PHPDoc blocks';
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getExample() {
		return <<<'EOT'
<?php
class A {
	function a(Someclass $a) {
		return 1;
	}
}
?>
to
<?php
class A {
	/**
	 * @param Someclass $a
	 * @return int
	 */
	function a(Someclass $a) {
		return 1;
	}
}
?>
EOT;
	}
}
