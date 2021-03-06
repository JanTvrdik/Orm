<?php
/**
 * PHP Token Reflection
 *
 * Version 1.0.0 RC 1
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this library in the file LICENSE.
 *
 * @author Ondřej Nešpor
 * @author Jaroslav Hanslík
 */

namespace TokenReflection;

/**
 * Common reflection constant interface.
 */
interface IReflectionConstant extends IReflection
{
	/**
	 * Returns the unqualified name (UQN).
	 *
	 * @return string
	 */
	public function getShortName();

	/**
	 * Returns the declaring class reflection.
	 *
	 * @return \TokenReflection\IReflectionClass
	 */
	public function getDeclaringClass();

	/**
	 * Returns the declaring class name.
	 *
	 * @return string
	 */
	public function getDeclaringClassName();

	/**
	 * Returns the namespace name.
	 *
	 * @return string
	 */
	public function getNamespaceName();

	/**
	 * Returns if the constant is defined within a namespace.
	 *
	 * @return boolean
	 */
	public function inNamespace();

	/**
	 * Returns the file name the reflection object is defined in.
	 *
	 * @return string
	 */
	public function getFileName();

	/**
	 * Returns the definition start line number in the file.
	 *
	 * @return integer
	 */
	public function getStartLine();

	/**
	 * Returns the definition end line number in the file.
	 *
	 * @return integer
	 */
	public function getEndLine();

	/**
	 * Returns the appropriate docblock definition.
	 *
	 * @return string|boolean
	 */
	public function getDocComment();

	/**
	 * Returns the constant value.
	 *
	 * @return mixed
	 */
	public function getValue();

	/**
	 * Returns the part of the source code defining the constant value.
	 *
	 * @return string
	 */
	public function getValueDefinition();

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString();
}
