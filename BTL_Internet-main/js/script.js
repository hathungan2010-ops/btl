'use strict';
document.querySelectorAll('form[data-confirm]').forEach(form=>{
  form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.confirm))event.preventDefault();});
});
const variant=document.querySelector('[data-variant-select]');
if(variant){
  const quantity=document.querySelector('[data-quantity]'),stock=document.querySelector('[data-stock]'),price=document.querySelector('[data-price]');
  const update=()=>{
    const option=variant.selectedOptions[0],amount=Number(option?.dataset.stock||0);
    quantity.max=String(Math.max(1,Math.min(99,amount)));
    if(Number(quantity.value)>amount)quantity.value=String(Math.max(1,amount));
    stock.textContent=amount?'Còn '+amount+' sản phẩm trong kho.':'Vui lòng chọn size và màu.';
    if(option?.dataset.price)price.textContent=new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND',maximumFractionDigits:2}).format(Number(option.dataset.price));
  };
  variant.addEventListener('change',update);update();
}
const upload=document.querySelector('[data-image-upload]');
if(upload)upload.addEventListener('change',()=>{
  const file=upload.files[0],preview=document.querySelector('[data-image-preview]');
  upload.setCustomValidity('');
  if(!file||!preview)return;
  if(file.size>5*1024*1024){upload.setCustomValidity('Ảnh tối đa 5 MB.');upload.reportValidity();return;}
  if(preview.dataset.objectUrl)URL.revokeObjectURL(preview.dataset.objectUrl);
  preview.src=URL.createObjectURL(file);preview.dataset.objectUrl=preview.src;
});
const add=document.querySelector('[data-add-variant]');
if(add){
  const rows=document.querySelector('[data-variant-rows]');let index=Number(rows.dataset.nextIndex);
  add.addEventListener('click',()=>{
    if(rows.children.length>=100)return;
    rows.insertAdjacentHTML('beforeend',document.querySelector('#variant-template').innerHTML.replaceAll('__INDEX__',String(index++)));
  });
  rows.addEventListener('click',event=>{
    const button=event.target.closest('[data-remove-variant]');
    if(button&&rows.children.length>1)button.closest('tr').remove();
  });
}
