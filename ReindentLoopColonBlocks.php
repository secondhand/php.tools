<?php
final class ReindentLoopColonBlocks extends FormatterPass {
	public function format($source) {
		$source = $this->format_for_blocks($source);
		$source = $this->format_foreach_blocks($source);
		$source = $this->format_while_blocks($source);
		return $source;
	}

	private function format_blocks($source, $open_token, $close_token) {
		$this->tkns = token_get_all($source);
		$this->code = '';

		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->get_token($token);
			$this->ptr       = $index;
			switch ($id) {
				case $close_token:
					$this->set_indent(-1);
					$this->append_code($text, false);
					break;
				case $open_token:
					$this->append_code($text, false);
					while (list($index, $token) = each($this->tkns)) {
						list($id, $text) = $this->get_token($token);
						$this->ptr       = $index;
						$this->append_code($text, false);
						if (ST_CURLY_OPEN === $id) {
							break;
						} elseif (ST_COLON === $id && !$this->is_token(array(T_CLOSE_TAG))) {
							$this->set_indent(+1);
							break;
						} elseif (ST_COLON === $id) {
							break;
						}
					}
					break;
				default:
					if (substr_count($text, $this->new_line) > 0 && !$this->is_token(array($close_token))) {
						$text = str_replace($this->new_line, $this->new_line . $this->get_indent(), $text);
					} elseif (substr_count($text, $this->new_line) > 0 && $this->is_token(array($close_token))) {
						$this->set_indent(-1);
						$text = str_replace($this->new_line, $this->new_line . $this->get_indent(), $text);
						$this->set_indent(+1);
					}
					$this->append_code($text, false);
					break;
			}
		}
		return $this->code;
	}
	private function format_for_blocks($source) {
		return $this->format_blocks($source, T_FOR, T_ENDFOR);
	}
	private function format_foreach_blocks($source) {
		return $this->format_blocks($source, T_FOREACH, T_ENDFOREACH);
	}
	private function format_while_blocks($source) {
		$this->tkns = token_get_all($source);
		$this->code = '';

		while (list($index, $token) = each($this->tkns)) {
			list($id, $text) = $this->get_token($token);
			$this->ptr       = $index;
			switch ($id) {
				case T_ENDWHILE:
					$this->set_indent(-1);
					$this->append_code($text, false);
					break;
				case T_WHILE:
					$this->append_code($text, false);
					while (list($index, $token) = each($this->tkns)) {
						list($id, $text) = $this->get_token($token);
						$this->ptr       = $index;
						$this->append_code($text, false);
						if (ST_CURLY_OPEN === $id) {
							break;
						} elseif (ST_SEMI_COLON === $id) {
							break;
						} elseif (ST_COLON === $id) {
							$this->set_indent(+1);
							break;
						}
					}
					break;
				default:
					if (substr_count($text, $this->new_line) > 0 && !$this->is_token(array(T_ENDWHILE))) {
						$text = str_replace($this->new_line, $this->new_line . $this->get_indent(), $text);
					} elseif (substr_count($text, $this->new_line) > 0 && $this->is_token(array(T_ENDWHILE))) {
						$this->set_indent(-1);
						$text = str_replace($this->new_line, $this->new_line . $this->get_indent(), $text);
						$this->set_indent(+1);
					}
					$this->append_code($text, false);
					break;
			}
		}
		return $this->code;
	}
}