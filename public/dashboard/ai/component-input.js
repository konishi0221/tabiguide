export default {
  props: {
    label: String,
    model_value: [String, Number, Boolean, Object, Array],
    schema: [String, Number, Boolean, Array, Object],
    name: String,
    placeholder: String
  },
  computed: {
    ph () {
      /* 1) props.placeholder が最優先 */
      if (this.placeholder !== undefined && this.placeholder !== '') return this.placeholder

      /* 2) boolean+note 用: schema.note を採用 */
      if (this.isBooleanNote && typeof this.schema.note === 'string') return this.schema.note

      /* 3) schema.placeholders.<field> を探す（CRUD 等）*/
      if (this.schema?.placeholders) {
        return this.schema.placeholders[this.fieldName] || ''
      }

      /* 4) schema が文字列ならそのまま */
      if (typeof this.schema === 'string') return this.schema
      return ''
    },
    isTextarea () {
      // 文字列 'textarea' か、型が object で type 指定
      return this.schema === 'textarea' ||
        (this.schema && typeof this.schema === 'object' && this.schema.type === 'textarea')
    },
    fieldName () { return this.name || this.label },
    isBooleanNote () {
      return this.schema && typeof this.schema === 'object' &&
        'value' in this.schema && 'note' in this.schema
    },
    isBoolean () { return typeof this.schema === 'boolean' },
    isNumeric () {
      return typeof this.schema === 'number' ||
        (!isNaN(this.model_value) && this.model_value !== '')
    },
    isSelect () { return Array.isArray(this.schema) && typeof this.schema[0] === 'string' },
    isCrudList () {
        return Array.isArray(this.schema) &&
        (this.schema.length === 0 || typeof this.schema[0] === 'object')

      // return Array.isArray(this.schema) && typeof this.schema[0] === 'object'
    },
    innerValue: {
      get () {
        return (this.model_value !== undefined && this.model_value !== false)
          ? this.model_value
          : ''
      },
      set (val) { this.$emit('update:model_value', val) }
    }
  },
  methods: {
    updateValue (val) { this.$emit('update:model_value', { ...this.model_value, value: val }) },
    updateNote (note) { this.$emit('update:model_value', { ...this.model_value, note }) },
    textareaRows () {
      if (this.schema && typeof this.schema === 'object' && this.schema.rows) {
        return this.schema.rows        // スキーマで rows 指定がある場合
      }
      return 3                         // デフォルト 3 行
    },
    addItem () {
      const newItem = {}
      Object.keys(this.schema[0]).forEach(k => { newItem[k] = '' })
      this.$emit('update:model_value', [...(this.model_value || []), newItem])
    },
    getPlaceholder (field) {
      const s0 = this.schema?.[0] || {}

      // 1) 明示的な placeholders がある
      if (s0.placeholders && typeof s0.placeholders[field] === 'string') {
        return s0.placeholders[field]
      }

      // 2) スキーマ自体が例文を持つ
      if (typeof s0[field] === 'string' && s0[field].trim() !== '') {
        return s0[field]
      }

      // 3) 何も無ければ空
      return ''
    },
    getLabel (fieldName, value) {
      const labelMap = {
        'base_data[施設タイプ]': {
          camp: 'キャンプ場', hotel: 'ホテル', ryokan: '旅館',
          guesthouse: 'ゲストハウス', villa: '貸別荘', minpaku: '民泊'
        }
      }
      return labelMap[fieldName]?.[value] ?? value
    }
  },
  template: `
  <div class="form-group" style="margin-bottom:1em;">
    <label>{{ label }}</label>

    <!-- boolean + note -->
    <div v-if="isBooleanNote" class="radio-group">
      <label><input type="radio" :name="name+'[value]'" value="1"
        :checked="model_value.value==1" @change="updateValue(true)" /> はい</label>
      <label><input type="radio" :name="name+'[value]'" value="0"
        :checked="model_value.value==0" @change="updateValue(false)" /> いいえ</label>
      <input type="text" :name="name+'[note]'" :value="model_value?.note||''"
        :placeholder="ph||'備考（任意）'"
        @input="updateNote($event.target.value)" />
    </div>

    <!-- select -->
    <select v-else-if="isSelect" :name="name" v-model="innerValue">
      <option disabled value="">{{ ph || '選択してください' }}</option>
      <option v-for="opt in schema" :key="opt" :value="opt">
        {{ getLabel(name, opt) }}
      </option>
    </select>

    <!-- CRUD 配列 -->
    <div v-else-if="isCrudList">
      <div v-for="(item, index) in model_value" :key="index" class="crud-item">
        <!-- タイトル -->
        <input
          :name="name + '[' + index + '][title]'"
          v-model="item.title"
          class="crud-title"
          :placeholder="getPlaceholder('title') || 'タイトル'"
        />

        <!-- 説明 -->
        <textarea
          :name="name + '[' + index + '][content]'"
          v-model="item.content"
          class="crud-content"
          rows="1"
          :placeholder="getPlaceholder('content') || '説明'"
        ></textarea>

        <!-- 金額 -->
        <input
          :name="name + '[' + index + '][price]'"
          v-model="item.price"
          class="crud-price"
          :placeholder="getPlaceholder('price') || '金額'"
        />

        <!-- 削除ボタン -->
        <button
          type="button"
          class="crud-delete"
          @click="model_value.splice(index, 1)"
        >
          削除
        </button>
      </div>

      <!-- 追加ボタン -->
      <button type="button" class="crud-add" @click="addItem">＋ 追加</button>
    </div>

    <!-- textarea -->
    <textarea
      v-else-if="isTextarea"
      :name="name"
      v-model="innerValue"
      :rows="textareaRows()"
      :placeholder="ph"
    ></textarea>


    <!-- boolean -->
    <div v-else-if="isBoolean" class="radio-group">
      <label><input type="radio" :name="name" value="1"
        :checked="model_value==1" @change="$emit('update:model_value',true)" /> ある</label>
      <label><input type="radio" :name="name" value="0"
        :checked="model_value==0" @change="$emit('update:model_value',false)" /> ない</label>
    </div>

    <!-- 数値 -->
    <input v-else-if="isNumeric" type="text" :name="name" v-model="innerValue"
      step="any" :placeholder="ph" />

    <!-- その他テキスト -->
    <input v-else type="text" :name="name" v-model="innerValue"
      :placeholder="ph" />
  </div>



  `
}
