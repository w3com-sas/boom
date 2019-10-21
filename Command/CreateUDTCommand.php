<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\HanaEntity\UserFieldsMD;
use W3com\BoomBundle\HanaEntity\UserTablesMD;
use W3com\BoomBundle\HanaRepository\UserFieldsMDRepository;
use W3com\BoomBundle\Service\BoomGenerator;
use W3com\BoomBundle\Service\BoomManager;

class CreateUDTCommand extends Command
{
    private $manager;
    private $generator;

    public function __construct(BoomManager $manager, BoomGenerator $generator)
    {
        $this->manager = $manager;
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('boom:make:udt')
            ->setDescription('Create UDT in SAP');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AnnotationRegistry::registerLoader('class_exists');

        $io = new SymfonyStyle($input, $output);

        $io->title("UDT Creation command");

        $entity = new Entity();
        
        $udt = new UserTablesMD();

        $udtCreated = false;

        while (!$udtCreated) {
            $udtName = $io->ask("What is your UDT name ?");
            $udtDescr = $io->ask("Give a description");

            $udtName = strpos(strtoupper($udtName), 'W3C_') === false ? 'W3C_' . strtoupper($udtName) : strtoupper($udtName);
            $udt->setTableName($udtName);
            $udt->setTableDescription($udtDescr);
            $udt->setTableType(UserTablesMD::TABLE_TYPE_OBJECT);
            $udt->setArchivable(UserTablesMD::ARCHIVABLE_NO);

            $udtRepo = $this->manager->getRepository('UserTablesMD');

            try {
                /** @var UserTablesMD $udt */
                $udtRepo->add($udt);
                $udtCreated = true;
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }
        }

        $fieldsName = ['Code', 'Name'];

        $io->section('Fields of UDT');

        $io->listing($fieldsName);

        $addingField = $io->confirm("Want you add a field to your UDT ?");

        $prefix = '';

        if ($addingField) {
            $addingPrefix = $io->confirm("Want you set a prefix to your fields ?");
            if ($addingPrefix) {
                $prefix = $io->ask('Prefix', 'W3C_');

                $io->success('Prefix ' . $prefix . ' confirmed !');
            }
        }

//        $fieldId = 0;

        while ($addingField) {
            $udf = new UserFieldsMD();

            $udfTable = '@' . strtoupper($udtName);

            $udfName = $io->ask("What is the name of the field ?");

            $udfName = $prefix . strtoupper($udfName);

            if (strlen($udfName) > 8) {
                $io->error('The field name ('.$udfName.') must contain less than 9 characters.');
                continue;
            }

            $udfDescr = $io->ask("Give a description");

            $udfType = $io->choice("What is the type of the field ?", [
                'Alpha',
                'Date',
                'Numeric',
                'Float',
                'Table Key'
            ]);

            $linkedField = false;

            $udfSubType = UserFieldsMD::SUBTYPE_NONE;
            $udfSubTypes = [];

            switch ($udfType) {
                case 'Alpha':
                    $udfType = UserFieldsMD::TYPE_ALPHA;
                    $udfSubTypes = [
                        'Normal' => UserFieldsMD::SUBTYPE_NONE,
                        'Text' => UserFieldsMD::SUBTYPE_NONE,
                        'Address' => UserFieldsMD::SUBTYPE_ADDRESS,
                        'Phone' => UserFieldsMD::SUBTYPE_PHONE
                    ];
                    break;
                case 'Numeric':
                    $udfType = UserFieldsMD::TYPE_NUMERIC;
                    break;
                case 'Float':
                    $udfType = UserFieldsMD::TYPE_FLOAT;
                    $udfSubTypes = [
                        'Rate' => UserFieldsMD::SUBTYPE_RATE,
                        'Amount' => UserFieldsMD::SUBTYPE_AMOUNT,
                        'Price' => UserFieldsMD::SUBTYPE_PRICE,
                        'Quantity' => UserFieldsMD::SUBTYPE_QUANTITY,
                        'Percentage' => UserFieldsMD::SUBTYPE_PERCENTAGE,
                        'Measurement' => UserFieldsMD::SUBTYPE_MEASUREMENT
                    ];
                    break;
                case 'Date':
                    $udfType = UserFieldsMD::TYPE_DATE;
                    $udfSubTypes = [
                        'Date' => UserFieldsMD::SUBTYPE_NONE,
                        'Time' => UserFieldsMD::SUBTYPE_TIME
                    ];
                    break;
                case 'Table Key':
                    $udfType = UserFieldsMD::TYPE_ALPHA;
                    $linkedField = true;
                    $inspector = $this->generator->getSLInspector();
                    $tables = [];

                    $inspector->initEntities();

                    /** @var Entity $entity */
                    foreach ($inspector->getUDTEntities() as $entity) {
                        $tables[] = substr($entity->getTable(), 2);
                    }

                    $linkedTableName = $io->choice('Wich table ?', $tables);

                    $udf->setLinkedTable($linkedTableName);

                    break;
            }

            if ($udfSubTypes !== []) {
                $udfSubType = $io->choice('Please chose a SubType to your field ?', array_keys($udfSubTypes));
                if ($udfSubType === 'Text') {
                    $udfType = UserFieldsMD::TYPE_MEMO;
                }
                $udfSubType = $udfSubTypes[$udfSubType];
            }

            if (($udfType === UserFieldsMD::TYPE_NUMERIC)
                || ($udfType === UserFieldsMD::TYPE_ALPHA)
                || ($udfType === UserFieldsMD::TYPE_MEMO)) {
                $defaultSize = $udfType === UserFieldsMD::TYPE_MEMO ? 254 : 10;
                $size = $io->ask('Size of the field', $defaultSize, function ($number) {
                    if (!is_numeric($number)) {
                        throw new \RuntimeException('You must type a number.');
                    }

                    return (int) $number;
                });
                $udf->setEditSize($size);
            }

            $validValues = [];
            $mandatory = false;
            $defaultValue = null;
            if (!$linkedField) {
                $choiceType = $io->confirm('Your UDF is a choice type ?', false);

                $num = 1;

                while ($choiceType) {
                    $io->title('Valid value n°' . $num);
                    $vValue = $io->ask('What is the value ?');
                    $vDescr = $io->ask('Give a description');
                    $validValues[] = [
                        'Value' => $vValue,
                        'Description' => $vDescr
                    ];
                    if (count($validValues) >= 2) {
                        $choiceType = $io->confirm('Want you add a valid value to your UDF ?');
                    }
                    $num++;
                }

                $mandatory = $io->confirm('Is it mandatory ?', false);

                $createDefaultValue = true;

                $defaultValue = '';

                while ($createDefaultValue) {
                    $defaultValue = $io->ask('What is the default value ? (Press \'return\' if no default value is defined)');

                    if ($defaultValue && $validValues !== []) {
                        foreach ($validValues as $validValue) {
                            if ($validValue['Value'] === $defaultValue) {
                                $createDefaultValue = false;
                                break;
                            }
                        }
                        if ($createDefaultValue) {
                            $io->error('Your default value is not in valid values !');
                            $io->title('Valid Values');
                            $io->listing($validValues);
                        }
                    } else {
                        $createDefaultValue = false;
                    }
                }
            }


            $udf->setName($udfName);
            $udf->setTableName($udfTable);
            $udf->setType($udfType);
            $udf->setSubType($udfSubType);
            $udf->setDescription($udfDescr);
            $udf->setMandatory($mandatory ?
                UserFieldsMD::MANDATORY_YES :
                UserFieldsMD::MANDATORY_NO
            );
            $udf->setValidValuesMD($validValues);

            if ($defaultValue) {
                $udf->setDefaultValue($defaultValue);
            }


            $udfCreated = false;
            $nbTour = 0;
            while (!$udfCreated && $nbTour < 5) {
                try {
                    /** @var UserFieldsMDRepository $udfRepo */
                    $udfRepo = $this->manager->getRepository('UserFieldsMD');

                    $udfExists = $udfRepo->findByTableNameAndFieldName('@' . $udtName, $udfName);

                    if ($udfExists === []) {
                        $udfRepo->add($udf);

                        $fieldsName[] = $udfName;

                        $io->success($udfName . ' added to UDT !');
                    } else {
                        throw new \Exception($udfName . ' already exists !');
                    }

                    $udfCreated = true;
                } catch (\Exception $e) {
                    $nbTour++;
                }
            }

            $io->section('Fields of UDT');

            $io->listing($fieldsName);

            $addingField = $io->confirm("Want you add a field to your UDT ?");
        }

        $io->success('You can now use ' . $udtName . ' in your project !');
    }
}