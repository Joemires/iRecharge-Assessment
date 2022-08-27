<?php

if (!function_exists('payment_gateway')) {

    /**
     * description
     *
     * @param
     * @return
     */
    function bind_payment_method($method = null)
    {
        $interface = 'App\Support\Payment\Gateways\Manager';
        $path = 'Support/Payment/Gateways/';

        $implementations = collect(File::allFiles(app_path($path)));

        $methods = $implementations
            ->transform( function($implementation) use ($interface, $path) {
                $classname = Str::of($implementation->getfilename())->before('.php')->prepend($path)->replace('/', '\\')->prepend('App\\')->toString();

                $reflection = new \ReflectionClass($classname);
                if(Arr::exists($reflection->getInterfaces(), $interface)) {
                    $instance = $reflection->newInstanceWithoutConstructor();

                    return [
                        'name' => $instance->getName(),
                        'class' => $classname
                    ];
                }
                return false;
            })
            ->recursive()
            ->filter();


        $method = $methods->first( fn($p) => $p->get('name') == config('payment.gateway'));

        if($methods) {
            app()->bind($interface, $method->get('class'));
            // dd($method);
            return true;
        }

        return false;
    }
}
