import { setLocale } from 'yup';
import { descriptive } from 'yup-locale-ja';
import { configure } from 'vee-validate';

// yupのエラーメッセージを日本語化
setLocale(descriptive);
// vee-validateのデフォルトのバリデーションタイミングを設定。
// 個別にuseFieldでも設定できる。
configure({
  validateOnBlur: true,
  validateOnChange: false,
  validateOnInput: false,
  validateOnModelUpdate: false,
});

export default defineNuxtPlugin(() => {
});
