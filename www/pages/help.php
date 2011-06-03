<?

function helppage($input, $user){
?>

There are 5 categories of players:<br>
- Human<br>
- Program<br>
- Baseline<br>
- Test Group<br>
- Test Case<br>
<br>
Humans are included to allow games between humans or between humans and programs to be logged.<br>
A program is a grouping of configurations. Its parameter is the adaptor class name.<br>
A baseline is a complete configuration of a program.<br>
A test troup is a group of test cases. Its parameter is prepended to the parameters of a test case.<br>
A test case is a partial configuration that is appended to a baseline to create a complete configuration.<br>
<br>
Two types of tournaments are run:<br>
- round robin of baselines across all programs<br>
- each baseline against each test case for that program<br>

<?
	return true;
}
