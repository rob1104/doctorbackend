<?php
$f = fopen('medicamentos.csv', 'w');
fputcsv($f, ['generic_name', 'commercial_name', 'presentation', 'active_substance', 'route', 'concentration', 'status']);
$gen = ['Paracetamol', 'Ibuprofeno', 'Amoxicilina', 'Omeprazol', 'Diclofenaco', 'Losartan', 'Metformina', 'Loratadina', 'Ketorolaco', 'Naproxeno', 'Azitromicina', 'Ceftriaxona', 'Dexametasona', 'Clonazepam', 'Fluoxetina', 'Sertralina', 'Salbutamol', 'Enalapril', 'Atorvastatina', 'Ciprofloxacino'];
$com = ['Tylenol', 'Advil', 'Amoxil', 'Prilosec', 'Voltaren', 'Cozaar', 'Glucophage', 'Claritin', 'Toradol', 'Aleve', 'Zithromax', 'Rocephin', 'Decadron', 'Klonopin', 'Prozac', 'Zoloft', 'Ventolin', 'Vasotec', 'Lipitor', 'Cipro'];
$pres = ['Tabletas', 'Cápsulas', 'Jarabe', 'Suspensión', 'Inyectable', 'Gotas', 'Pomada', 'Crema', 'Supositorios', 'Inhalador'];
$routes = ['Oral', 'Intramuscular', 'Intravenosa', 'Tópica', 'Oftálmica', 'Ótica', 'Inhalatoria', 'Sublingual', 'Rectal'];
$conc = ['500 mg', '400 mg', '250 mg', '20 mg', '50 mg', '100 mg', '850 mg', '10 mg', '100 ml', '5 ml', '1 g', '500 mcg', '2 mg', '50 mg/ml'];
for ($i=0; $i<1000; $i++) {
    $idx = array_rand($gen);
    $c = $com[$idx] . ' ' . (rand(1, 9)*100);
    fputcsv($f, [$gen[$idx], $c, $pres[array_rand($pres)], $gen[$idx], $routes[array_rand($routes)], $conc[array_rand($conc)], 'active']);
}
fclose($f);
echo "CSV generado con 1000 medicamentos.\n";
