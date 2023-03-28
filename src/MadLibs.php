<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;


//#[AsCommand(name: 'MadLibs')]
class MadLibs extends Command
{

    protected static $defaultName = 'MadLibs';

    private string $mad_libs_filename = 'madlibs.json';


    protected function configure(): void
    {
        $this->setDescription("Allows user to input mad libs to the console.");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // find mad libs file
        $finder = new Finder();
        $finder->files()->in('src/data')->name($this->mad_libs_filename);

        # if file not found, throws FileNotFoundException
        if ($finder->hasResults()) {
            // iterate through Finder object to retrieve the file
            foreach ($finder as $file) {

                if ($file->getFilename() == $this->mad_libs_filename) {
                    $madLibsFileContents = $file->getContents();
                    $madLibsArray = json_decode($madLibsFileContents, true);
                    $madLibsOptions = array(); # to be: array of indexed mad libs titles

                    // build madLibsOptions array of indexed titles
                    $keyIndex = 0;
                    foreach ($madLibsArray as $madLib) {
                        $value = $madLib["title"];
                        $option = array($keyIndex => $value);

                        $madLibsOptions = array_merge($madLibsOptions, $option);
                        $keyIndex++;
                    }

                    // print the titles and indices of the mad libs to the console
                    $this->printTitleAndOptions($input, $output, $madLibsOptions);

                    // PROMPT USER TO ENTER INDEX OF MAD LIBS SELECTION
                    // CHECK IF THE SELECTED INDEX IS WITHIN THE RANGE
                    $selectionIndex = -1;
                    while ($selectionIndex < 0 || $selectionIndex > count($madLibsOptions)) {
                        $selectionIndex = $this->getSelection($input, $output);
                        if ($selectionIndex < 0 || $selectionIndex >= count($madLibsOptions)) {
                            $output->writeln('<error>ERROR: You must enter a value between [0] and '
                                . (count($madLibsOptions) - 1) . '. Please try again.</error>');
                        }
                    }

                    $selection = $madLibsArray[$selectionIndex]; # the mad lib selected by the user
                    $blanks = (array)$selection["blanks"]; # array of the selected mad lib blanks

                    // PROMPT USER TO FILL IN EACH INDIVIDUAL BLANK
                    $this->printSeparator($input, $output);
                    $responses = $this->getResponses($input, $output, $blanks);

                    // BUILD AND PRINT THE COMPLETED MAD LIB STRING
                    // NOTE: The following assumes that a blank is always the second item in a mad lib.
                    $values = $selection["value"]; # mad lib non-blanks, titled 'values' in madlibs.json
                    $result = $this->buildMadLib($responses, $values);
                    $this->printSeparator($input, $output);
                    $output->writeln($result);

                    if ($this->getPlayAgainResponse($input, $output) == '1') {
                        $this->execute($input, $output);
                    }
                }
            }
        } else {
            throw new FileNotFoundException('<error>ERROR: ' . $this->mad_libs_filename
                                            . ' file not found.</error>');
        }

        return Command::SUCCESS;
    }


    private function getPlayAgainResponse(InputInterface $input, OutputInterface $output): string {
        $helper = $this->getHelper('question');

        $this->printSeparator($input, $output);
        $question = new Question('<question>Enter 1 if you would like to play again: </question>', 0);

        return $helper->ask($input, $output, $question);
    }

    private function getSelection(InputInterface $input, OutputInterface $output): int {
        $helper = $this->getHelper('question');

        $this->printSeparator($input, $output);
        $question = new Question('Please enter the number for a Mad Lib '
            . 'from the options above:  ', 0);

        return $helper->ask($input, $output, $question);
    }

    private function getResponses(InputInterface $input, OutputInterface $output, array $blanks): array {
        $output->writeln("Fill in the blanks: ");
        $helper = $this->getHelper('question');
        $responses = array();
        foreach ($blanks as $blank) {
            $question = new Question($blank . ": ", "---");
            $response = $helper->ask($input, $output, $question);
            $responses[] = $response;
        }

        return $responses;
    }

    private function buildMadLib(array $responses, array $values): string {
        $result = ""; # final string resulting from putting together blanks and values

        $numValues = count($values);
        $numResponses = count($responses);
        $valuesIndex = 0;
        $responsesIndex = 0;

        while ($valuesIndex < $numValues && $responsesIndex < $numResponses) {
            $result .= $values[$valuesIndex++];
            $result .= $responses[$responsesIndex++];
        }
        // add remaining items from values and responses arrays to the result string
        while ($valuesIndex < $numValues) {
            $result .= $values[$valuesIndex++];
        }
        while ($responsesIndex < $numResponses) {
            $result .= $responses[$responsesIndex++];
        }

        return $result;
    }

    private function printSeparator(InputInterface $input, OutputInterface $output) {
        $output->writeln("=======================================================================");
    }

    private function printTitleAndOptions(InputInterface $input, OutputInterface $output, array $madLibsOptions) {
        $output->writeln("\n======================================================================="
                                . "\n                              MAD  LIBS                                "
                                . "\n=======================================================================\n");

        $keyIndex = 0;
        foreach ($madLibsOptions as $optionTitle) {
            $output->writeln('[' . $keyIndex . '] : <info>' . $optionTitle . '</info>');
            $keyIndex++;
        }

        $output->writeln("\n=======================================================================\n");
    }

}