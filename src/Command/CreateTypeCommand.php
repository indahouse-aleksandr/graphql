<?php
namespace RzCommon\graphql\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\{
    InputArgument,
    InputInterface,
    InputDefinition,
    InputOption
};
use RzCommon\graphql\Service\{
    CreateTypeService,
    DBService
};

class CreateTypeCommand extends Command
{

    protected static $defaultName = 'app:create-type';

    /**
     * @var CreateTypeService
     */
    protected $createTypeService;

    /**
     * @var DBService
     */
    protected $dbService;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->createTypeService = new CreateTypeService();
        $this->dbService = new DBService();
        parent::__construct();
    }

    /**
     * Configuration
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Create new GraphQL type')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('entity', InputArgument::REQUIRED, 'Name of new type'),
                    new InputOption(
                        'is_object', 'obj', InputOption::VALUE_OPTIONAL, 'If set to true then resolver will be using entity instead array', false
                    ),
                    new InputOption('table', 't', InputOption::VALUE_OPTIONAL, 'If set table name, we get info on fields from schema')
                ])
            );
    }

    /**
     * Check params and return them
     *
     * @return array
     */
    public function getParams(InputInterface $input): array
    {
        $params = [
            'registry' => $_ENV['TYPE_REGISTRY_PATH'],
            'type_folder' => $_ENV['TYPE_FOLDER_PATH'],
            'type_namespace' => $_ENV['TYPE_NAMESPACE'],
            'is_object' => $input->getOption('is_object')
        ];
        if (!file_exists($params['registry'])) {
            throw new \Exception("You have not set TYPE_REGISTRY_PATH!");
        }
        if (!is_dir($params['type_folder'])) {
            throw new \Exception("You have not set TYPE_FOLDER_PATH!");
        }
        return $params;
    }

    /**
     * Get params from user and collect information about graphQl type fields
     *
     * @return array['name' => string, 'type' => string];
     */
    private function getTypeFields(InputInterface $input, OutputInterface $output): array
    {
        return ($tableName = $input->getOption('table')) ? $this->getFromDb($tableName) : $this->getFromInput($input, $output); 
    }

    /**
     * Get fields from DB
     *
     * @return array
     */
    public function getFromDb(string $tableName): array
    {
        $this->dbService->makeConnect($_ENV['DATABASE_URL']);
        return $this->dbService->getFieldsFromDatabaseTable($tableName);
    }

    /**
     * Get fields from input
     *
     * @return array
     */
    private function getFromInput(InputInterface $input, OutputInterface $output): array
    {
        $fields = [];
        $helper = $this->getHelper('question');
        while (true) {
            $question = new Question('<question>Enter field name:</question> ');
            $fieldName = $helper->ask($input, $output, $question);
            if (!$fieldName) {
                break;
            }
            $question = new Question('<question>Enter field type (method in your TypeRegistry):</question> ');
            $fieldType = $helper->ask($input, $output, $question);
            $fields[] = ['name' => $fieldName, 'type' => $fieldType];
        }
        return $fields;
    }

    /**
     * Execute command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $params = $this->getParams($input);
        $typeName = $input->getArgument('entity');
        // get fields
        $typeFields = $this->getTypeFields($input, $output);
        // create type class file
        $info = $this->createTypeService->createTypeFile($typeName, $typeFields, $params);

        $output->writeln("<info>Create " . $info['typeFile'] . "</info>");
        $output->writeln("<info>Added $typeName property and method in " . $info['registryPath'] . "</info>");
    }
}