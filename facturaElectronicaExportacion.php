<?php


class facturaElectronicaExportacion {

    # Atributos de la clase

    public $factura = array();    
    private $cbte_nro = 0; # Inicializo el número de comprobante en cero. Luego será obtenido el número a facturar
    
    

    public function setFactura($factura='')
    {
        $this->factura = $factura;
    }

    public function getFactura()
    {
        return $this->factura;
    }

    public function getUltimoNumeroComrpobate()
    {
        $exec = "python ./recex1.py conf/rece.ini /ult " . $this->factura['tipo_cbte'] . " " . $this->factura['punto_vta'];
        $resultado_ejecucion = exec($exec);
        $ultimo_numero = intval($resultado_ejecucion);
        return $ultimo_numero;
    }

    public function setUltimoNumeroComprobante($ultimo_numero='')
    {
        $this->factura['cbte_nro'] = $ultimo_numero;
    }


    /*
    Método que se encarga de escribir el contenido de la variable $factura, en un archivo JSON con nombre factura.json
    Luego este archivo será leído por el script de python recex1.py para remitir a AFIP la factura de exportación
    Finalmente el archivo será re escrito por el script recex1.py con el resultado de la operación
    */
    public function escribirArchivoJsonFactura()
    {
        file_put_contents('./factura.json', json_encode(array($this->factura))) or die("Error #1 al escribir el archivo factura.json. Revise permisos de arhivos.");
    }

    // Se utiliza para obtener la cotización de la moneda pasada por parametro desde un WS de AFIP
    public function getCotizacionMoneda($moneda_id='DOL')
    {
        $exec = "python ./recex1.py conf/rece.ini /ctz " . $moneda_id ;
        $resultado_ejecucion = exec($exec);
        $cotizacion_moneda = floatval($resultado_ejecucion);        
        return $cotizacion_moneda;
    }

    public function setCotizacionMoneda($cotizacion_moneda)
    {
        $this->factura['moneda_ctz'] = $cotizacion_moneda;
    }

    // Método que se encarga de ejecutar el script recex1.py para obtener el CAE de la factura que reside en el archivo factura.json
    public function obtenerCAE()
    {
        $exec = "python ./recex1.py conf/rece.ini /cae";
        $cae = exec($exec);
        // El resultado de la operación se escribe en el archivo factura.json        
    }


    // Método interno que sirve para mostrar por pantalla una salida
    public function debug($var){
        echo "<pre>";
        echo print_r($var);
        echo "</pre>";        
    }


    public function getJsonResultadoOperacion()
    {
        $factura = file_get_contents('./factura.json') or die("Error #2 al leer el archivo factura.json. Revise permisos de arhivos.");
        $factura_decodificada = json_decode($factura);
        
        $factura = array(
            'resultado' => $factura_decodificada[0]->resultado,
            'err' => $factura_decodificada[0]->err,
            'cbte_nro' => $factura_decodificada[0]->cbte_nro,
            'reproceso' => $factura_decodificada[0]->reproceso,
            'obs' => $factura_decodificada[0]->obs,
            'cae' => $factura_decodificada[0]->cae,
        );

        return json_encode($factura);
    }
    
}

# Incializo el array de la factura
$item_factura = array(
    'codigo' => 'PRO1', # Código de producto                 
    'ds' => 'Producto Tipo 1 Exportacion MERCOSUR ISO 9001', # Descripción
    'qty' => 2, # Cantidad
    'umed' => 1, # Unidad de medida
    'precio' => "150.00",
    'bonif' => "0.00",
    'importe' => "300.00",
);

$permiso_existente = array(
    'id_permiso' => '99999AAXX999999A',
    'dst_merc' => '225',
);

$factura_a_enviar = array(
    'tipo_cbte'=> 19, // Tipo de comprobante 19 Factura Exportación ***OBLIGATORIO***
    'punto_vta'=> 7, // ***OBLIGATORIO***
    'cbte_nro'=> 0, // Número del comprobante que se solicita autorización ***OBLIGATORIO***
    'fecha_cbte' => date('Ymd'),  // Fecha del comprobante (yyyymmdd) NO obligatorio
    'fecha_pago' => date('Ymd'),
    'tipo_doc' => '', 
    'nro_doc' =>  '',
    'imp_total' => "300.00", // *** OBLIGATORIO ***
    'permiso_existente' => NULL, // Indica si se posee documento aduanero de exportación (permiso de embarque). Posibles valores: S, N, NULL ***OBLIGATORIO***
    'pais_dst_cmp' => 203, // País destino del comprobante ***OBLIGATORIO***
    'nombre_cliente' => "Joao Da Silva", // ***OBLIGATORIO***
    'domicilio_cliente' => "Rua N° 76 KM 8", // ***OBLIGATORIO***
    'id_impositivo' => "PJ54482221-l", // Clave de identificación tributaria del comprador. ***OBLIGATORIO***
    'moneda_id' => "DOL", // Código de moneda ***OBLIGATORIO***
    'moneda_ctz' => "8.00", // Cotización de moneda ***OBLIGATORIO***
    'obs_comerciales' => "", // No obligatorio
    'obs_generales' => "", // No Obligatorio
    'forma_pago' => "", // No Obligatorio
    'incoterms' => "", // Incoterms - Cláusula de Venta. NO obligatorio
    'incoterms_ds' => "", // Información complementaria de incoterm. NO obligatorio
    'tipo_expo' => 2, // Tipo de Exportación. 1 -> Exportación definitiva de bienes, 2 -> Exportación de servicios, 4 -> Otros ***OBLIGATORIO***
    'idioma_cbte' => 1, // Idioma del comprabante. 1 -> Español, 2-> Inglés, 3 -> Portugués ***OBLIGATORIO***
    'cbtes_asoc' => [], // NO OBLIGATORIO, se usa para notas de débito y crédito
    'permisos' => [], // No obligatorio
    'detalles' => $item_factura,    
    'resultado' => '', // Valor a ser llenado luego de emitir la factura
    'cae' => '', // Valor a ser llenado luego de emitir la factura
    'obs' => '', // Valor a ser llenado luego de emitir la factura
    'err' => '', // Valor a ser llenado luego de emitir la factura
    'reproceso' => '', // Valor a ser llenado luego de emitir la factura
);

#0. Creo el objeto Factura Electronica de Exportación. Construyo el array de la factura de testing por medio del constructor
$fee = new FacturaElectronicaExportacion();
echo "Paso 0 OK <br>";

#1. Seteo el array a facturar
$fee->setFactura($factura_a_enviar);
echo "Paso 1 OK <br>";

#2. Obtengo la cotizacion de la moneda
$cotizacion_moneda = $fee->getCotizacionMoneda();
echo "Paso 2 OK <br>";

#3. Seteo la cotizacion de la moneda obtenida
$fee->setCotizacionMoneda($cotizacion_moneda);
echo "Paso 3 OK <br>";

#4. Obtengo el último número de factura
$last_number = $fee->getUltimoNumeroComrpobate();
echo "Paso 4 OK <br>";

#5. Seteo en el array de factura el último numero de la factura
$fee->setUltimoNumeroComprobante($last_number);
echo "Paso 5 OK <br>";

#6. Escribo el archivo factura.json con los datos a facturar
$fee->escribirArchivoJsonFactura();
echo "Paso 6 OK <br>";

# Muestro por pantalla la factura a enviar
$fee->debug($fee->getFactura());

#7. Obtengo el CAE
$fee->obtenerCAE();
echo "Paso 7 OK <br>";

#8. Leo y muestro el archivo resultante
print_r($fee->getJsonResultadoOperacion());

?>