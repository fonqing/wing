<?php

namespace wing\libs;
use think\facade\Config;

class Security
{
    /**
     * Encrypt data with key
     *
     * @param  mixed  $data 要加密的数据
     * @param  string $key  加密密钥
     * @return string
     */
    public static function encode(mixed $data, string $key = ''): string
    {
        $iv = Config::get('app.security_iv', 'a_o_m_a_s_o_f_t_');
        $iv = substr(str_pad($iv, 16, '0'), 0, 16);
        return base64_encode(openssl_encrypt(serialize($data), 'AES-256-CBC', $key ?: $iv, 0, $iv));
    }

    /**
     * Decrypt data with key
     *
     * @param  mixed  $data 要解密的数据
     * @param  string $key  解密密钥
     * @return mixed
     */
    public static function decode(mixed $data, string $key = ''): mixed
    {
        $iv = Config::get('app.security_iv', 'a_o_m_a_s_o_f_t_');
        $iv = substr(str_pad($iv, 16, '0'), 0, 16);
        $data = openssl_decrypt(base64_decode($data), 'AES-256-CBC', $key ?: $iv, 0, $iv);
        return unserialize($data);
    }
}
