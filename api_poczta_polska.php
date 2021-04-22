
<?php

class ApiPP
{
    public $soap = null;
    private $header = null;
    private $url = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private $p = 'https://tt.poczta-polska.pl/Sledzenie/services/Sledzenie?wsdl';
    private $response = null;

    public function __construct($user = 'sledzeniepp', $pass = 'PPSA')
    {
        $Auth           = new \stdClass();
        $Auth->Username = new \SoapVar($user, XSD_STRING, null, $this->url, null, $this->url);
        $Auth->Password = new \SoapVar($pass, XSD_STRING, null, $this->url, null, $this->url);

        $UsernameToken                = new \stdClass();
        $UsernameToken->UsernameToken = new \SoapVar(
            $Auth,
            SOAP_ENC_OBJECT,
            null,
            $this->url,
            'UsernameToken',
            $this->url
        );

        $header = new \SoapVar(
            new \SoapVar($UsernameToken, SOAP_ENC_OBJECT, null, $this->url, 'UsernameToken', $this->url),
            SOAP_ENC_OBJECT,
            null,
            $this->url,
            'Security',
            $this->url
        );

        $this->header = new SoapHeader($this->url, 'Security', $header, true);
        $this->soap = new SoapClient($this->p);

        $this->soap->__setSoapHeaders(array($this->header));
    }

    public function getStatus($number)
    {
        $this->response = $this->soap->sprawdzPrzesylke(array(
            'numer' => $number
        ));

        if (!isset($this->response->return)) {
            throw new Exception("Nienawiązano połaczenia z pocztą polską");
        }

        return $this;
    }

    public function getResponse()
    {
        return $this->response->return;
    }

    public function getList()
    {
        $package = $this->getResponse();

        if (isset($package->danePrzesylki)) {
            $data = [
                'package_type' => $package->danePrzesylki->rodzPrzes,
                'country' => $package->danePrzesylki->krajNadania,
                'number' => $package->danePrzesylki->numer,
                'points' => []
            ];
            
            if (isset($package->danePrzesylki->zdarzenia->zdarzenie)) {
                foreach ($package->danePrzesylki->zdarzenia->zdarzenie as $point) {
                    $data['points'][] = [
                        'time' => $point->czas,
                        'name' => $point->nazwa,
                    ];
                }
            }

            return $data;
        }
        return false;
    }

    public function printList()
    {
        $html = '<ul>';
        
        if ($lists = $this->getList()) {
            $html .= sprintf('<li>TYP: %s</li>', $lists['package_type']);
            $html .= sprintf('<li>KRAJ: %s</li>', $lists['country']);
            $html .= sprintf('<li>NR: %s</li>', $lists['number']);
            
            foreach ($lists['points'] as $list) {
                $html .= sprintf('<li><small>%s</small></br>%s</li>', $list['time'], $list['name']);
            }

        } else {
            $html .= '<li>Brak danych o przesyłce</li>';
        }

        $html .= '</ul>';
        return $html;
    }
}

echo  packageCheck('0000000000000000000');//numer paczki sledzenia

function packageCheck($number)
{
    return (new ApiPP())->getStatus($number)->printList();
}
