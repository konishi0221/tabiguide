export default {
  props: {
    label: String,
    model_value: [String, Number, Boolean, Object, Array],
    schema: [String, Number, Boolean, Array, Object],
    name: String
  },
  computed: {
    fieldName() {
      return this.name || this.label;
    },
    isBooleanNote() {
      return this.schema && typeof this.schema === 'object' &&
             'value' in this.schema && 'note' in this.schema;
    },
    isBoolean() {
      return typeof this.schema === 'boolean';
    },
    isNumeric() {
      return typeof this.schema === 'number' || (!isNaN(this.model_value) && this.model_value !== '');
    },
    isSelect() {
      return Array.isArray(this.schema) && typeof this.schema[0] === 'string';
    },
    isCrudList() {
      return Array.isArray(this.schema) && typeof this.schema[0] === 'object';
    },
    innerValue: {
      get() {
        return this.model_value !== undefined && this.model_value !== false ? this.model_value : '';
      },
      set(val) {
        this.$emit('update:model_value', val);
      }
    }
  },
  methods: {
    updateValue(val) {
      this.$emit('update:model_value', { ...this.model_value, value: val });
    },
    updateNote(note) {
      this.$emit('update:model_value', { ...this.model_value, note });
    },
    addItem() {
      const newItem = {};
      Object.keys(this.schema[0]).forEach(k => {
        newItem[k] = "";
      });
      this.$emit('update:model_value', [...(this.model_value || []), newItem]);
    },
    getLabel(fieldName, value) {
  // フィールド名ごとのマッピング
      const labelMap = {
        'base_data[施設タイプ]': {
          camp: 'キャンプ場',
          hotel: 'ホテル',
          ryokan: '旅館',
          guesthouse: 'ゲストハウス',
          villa: '貸別荘',
          minpaku: '民泊'
        },
        // 他の select にも拡張可能
      };

      return labelMap[fieldName]?.[value] ?? value;
    }
  },
  template: `
  <div class="form-group" style="margin-bottom: 1em;">
    <label>{{ label }}</label>

    <!-- boolean + note -->
    <div v-if="isBooleanNote" class="radio-group">
      <label>
        <input
          type="radio"
          :name="name + '[value]'"
          value="1"
          :checked="model_value.value == 1"
          @change="updateValue(true)"
        />
        ある
      </label>
      <label>
        <input
          type="radio"
          :name="name + '[value]'"
          value="0"
          :checked="model_value.value == 0"
          @change="updateValue(false)"
        />
        ない
      </label>
      <input
        type="text"
        :name="name + '[note]'"
        :value="model_value?.note || ''"
        placeholder="備考（任意）"
        @input="updateNote($event.target.value)"
      />
    </div>

    <!-- select -->
    <div v-else-if="isSelect">
      {{ getLabel(name, innerValue) }}
    </div>

    <!-- CRUD配列 -->
    <div v-else-if="isCrudList">
      <div
        v-for="(item, index) in model_value"
        :key="index"
        class="crud-item"
      >
        <input
          :name="name + '[' + index + '][title]'"
          v-model="model_value[index].title"
          placeholder="タイトル"
        />
        <input
          :name="name + '[' + index + '][content]'"
          v-model="model_value[index].content"
          placeholder="説明"
        />
        <input
          :name="name + '[' + index + '][price]'"
          v-model="model_value[index].price"
          placeholder="金額"
        />
        <button type="button" @click="model_value.splice(index, 1)">削除</button>
      </div>

      <button type="button" @click="addItem">＋ 追加</button>
    </div>

    <!-- boolean -->
    <div v-else-if="isBoolean" class="radio-group">
      <label>
        <input
          type="radio"
          :name="name"
          value="1"
          :checked="model_value == 1"
          @change="$emit('update:model_value', true)"
        />
        ある
      </label>
      <label>
        <input
          type="radio"
          :name="name"
          value="0"
          :checked="model_value == 0"
          @change="$emit('update:model_value', false)"
        />
        ない
      </label>
    </div>

    <!-- 数値 -->
    <input
      v-else-if="isNumeric"
      type="number"
      :name="name"
      v-model="innerValue"
      step="any"
    />

    <!-- その他テキスト -->
    <input
      v-else
      type="text"
      :name="name"
      v-model="innerValue"
    />
  </div>
  `
};
