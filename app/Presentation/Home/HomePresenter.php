<?php
namespace App\Presentation\Home; 

use Nette;
use Nette\Application\UI\Form;

class HomePresenter extends Nette\Application\UI\Presenter 
{
    public function __construct(
        private Nette\Database\Explorer $database
    ) {}

    /**
     * Zobrazení seznamu produktů s možností filtrace.
     */
    public function renderDefault(?string $search = null): void 
    {
        $products = $this->database->table('products');

        // Textové vyhledávání
        if ($search) {
            $products->where('name LIKE ? OR code LIKE ?', "%$search%", "%$search%");
        }

        // Seřazení od nejnovějších
        $this->template->products = $products->order('created_at DESC');
        $this->template->search = $search;
    }

    /**
     * Komponenta formuláře pro nahrávání CSV souborů.
     */
    protected function createComponentImportForm(): Form
    {
        $form = new Form;
        $form->addUpload('csvFile', 'CSV Soubor:')
            ->setRequired('Vyberte prosím soubor.')
            ->addRule(function (Nette\Forms\Controls\UploadControl $control): bool {
                $file = $control->getValue();
                if (!$file->isOk()) return false;
                
                $ext = strtolower(pathinfo($file->getUntrustedName(), PATHINFO_EXTENSION));
                return in_array($ext, ['csv', 'txt'], true);
            }, 'Soubor musí mít příponu .csv nebo .txt');

        $form->addSubmit('send', 'Importovat');
        $form->onSuccess[] = [$this, 'importSucceeded'];
        
        return $form;
    }

    /**
     * Komponenta formuláře pro vytváření a editaci produktů.
     */ 
    protected function createComponentProductForm(): Form 
    {
        $form = new Form;
        $form->addHidden('id'); 
        
        $form->addText('code', 'Kód produktu:')
            ->setRequired('Zadejte prosím kód produktu.');

        $form->addText('name', 'Název:')
            ->setRequired('Zadejte prosím název.');

        $form->addTextArea('description', 'Popis:');

        $form->addCheckbox('active', ' Produkt je aktivní')
            ->setDefaultValue(true);

        $form->addInteger('stock', 'Skladem:')
            ->setDefaultValue(0)
            ->addRule($form::MIN, 'Skladem nemůže být záporný počet kusů.', 0);

        $form->addText('price', 'Cena:')
            ->setRequired('Zadejte prosím cenu.')
            ->addRule($form::PATTERN, 'Neplatný formát ceny.', '^[0-9]+([,.][0-9]+)?$');

        $form->addSubmit('send', 'Uložit produkt');

        $form->onSuccess[] = function (Form $form, $data) {
            $id = $data->id;
            $values = (array) $data; 
            unset($values['id']); 

            $values['price'] = str_replace(',', '.', $values['price']);
            $values['price'] = max(0, (float) $values['price']);

            if ($id) {
                $this->database->table('products')->get($id)->update($values);
                $this->flashMessage('Produkt byl upraven.');
            } else {
                $this->database->table('products')->insert($values);
                $this->flashMessage('Produkt byl přidán.');
            }

            $this->redirect('default');
        };

        return $form;
    }

    /**
     * Zpracování CSV souboru a synchronizace s databází.
     */
    public function importSucceeded(Form $form, $data): void 
{
    if (!$data->csvFile->isOk()) return;

    $file = fopen($data->csvFile->getTemporaryFile(), 'r');
    fgetcsv($file, 0, ';'); 

    while (($line = fgetcsv($file, 0, ';')) !== false) {
        if (count($line) < 5) {
            continue; 
        }

        $code = $line[0];
        $name = $line[1];
        $description = $line[2]; 

        $price = str_replace(',', '.', $line[3]);
        $price = max(0, (float) $price);
        
        $stock = max(0, (int) $line[4]);

        $active = isset($line[5]) ? (int) $line[5] : 0;

        $productData = [
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'active' => $active,
        ];

        $existing = $this->database->table('products')->where('code', $code)->fetch();
        
        if ($existing) {
            $existing->update($productData); 
        } else {
            $this->database->table('products')->insert($productData); 
        }
    }

    fclose($file);
    $this->flashMessage('Import byl úspěšně dokončen!');
    $this->redirect('this');
}

    /**
     * Načtení dat produktu pro editační formulář.
     */
    public function actionEdit(?int $id = null): void 
    {
        if ($id) { 
            $product = $this->database->table('products')->get($id);
            if (!$product) {
                $this->error('Produkt nenalezen');
            }
            $this['productForm']->setDefaults($product->toArray());
        }
    }

    /**
     * Signál pro odstranění produktu z databáze.
     */
    public function handleDelete(int $id): void 
    {
        $this->database->table('products')->get($id)->delete();
        $this->flashMessage('Produkt byl smazán.');
        $this->redirect('this');
    }

    /**
     * API endpoint pro detail produktu ve formátu JSON.
     */
    public function actionApiDetail(string $code): void 
    {
        $product = $this->database->table('products')->where('code', $code)->fetch();
        if (!$product) {
            $this->sendJson(['status' => 'error', 'message' => 'Produkt nenalezen']);
        }
        $this->sendJson($product->toArray());
    }

    /**
     * Globální kontrola oprávnění uživatele.
     */
    protected function startup(): void 
    {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
}